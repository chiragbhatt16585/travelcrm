<?php
declare(strict_types=1);

function quotation_pdf_filename(array $quote): string
{
    $option = quotation_option_label($quote);
    $filename = trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $quote['client_name'] . '-' . $quote['title'] . '-' . $option), '-');
    $filename = (string) preg_replace('/-+/', '-', $filename);
    if ($filename === '') {
        $filename = 'quotation';
    }
    return $filename . '.pdf';
}

function quotation_remove_dir(string $path): void
{
    if (!is_dir($path)) {
        return;
    }
    foreach (scandir($path) ?: [] as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $child = $path . '/' . $item;
        if (is_dir($child)) {
            quotation_remove_dir($child);
        } else {
            @unlink($child);
        }
    }
    @rmdir($path);
}

/** @return array{dir: string, path: string, filename: string} */
function quotation_render_pdf(string $html, string $filename): array
{
    $chrome = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
    if (!is_executable($chrome)) {
        throw new RuntimeException('Google Chrome is required on this computer to create the PDF.');
    }

    $dir = sys_get_temp_dir() . '/tw-quote-' . bin2hex(random_bytes(4));
    mkdir($dir, 0700, true);
    $htmlPath = $dir . '/quote.html';
    $pdfPath = $dir . '/quote.pdf';
    file_put_contents($htmlPath, $html);

    $cmd = escapeshellarg($chrome)
        . ' --headless=new --disable-gpu --no-sandbox --disable-dev-shm-usage'
        . ' --allow-file-access-from-files --no-pdf-header-footer --virtual-time-budget=10000'
        . ' --user-data-dir=' . escapeshellarg($dir . '/profile')
        . ' --print-to-pdf=' . escapeshellarg($pdfPath)
        . ' ' . escapeshellarg('file://' . $htmlPath);

    $process = proc_open($cmd, [1 => ['file', $dir . '/chrome.log', 'w'], 2 => ['file', $dir . '/chrome.log', 'a']], $pipes);
    $started = time();
    if (is_resource($process)) {
        do {
            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }
            if (time() - $started > 20) {
                proc_terminate($process);
                break;
            }
            usleep(200000);
        } while (true);
        proc_close($process);
    }

    if (!is_file($pdfPath) || filesize($pdfPath) < 800) {
        quotation_remove_dir($dir);
        throw new RuntimeException('The PDF could not be created.');
    }

    $named = $dir . '/' . $filename;
    if (!rename($pdfPath, $named)) {
        quotation_remove_dir($dir);
        throw new RuntimeException('The PDF could not be prepared.');
    }

    return ['dir' => $dir, 'path' => $named, 'filename' => $filename];
}

/** @return array{dir: string, path: string, filename: string} */
function quotation_create_pdf(array $quote): array
{
    $id = (int) $quote['id'];
    $packages = quotation_hotel_packages($id);
    $stays = $packages[0]['stays'] ?? quotation_stays($id);
    $html = quotation_sheet_document($quote, quotation_days($id), $stays, site_profile(), true, $packages);
    return quotation_render_pdf($html, quotation_pdf_filename($quote));
}

function quotation_copy_for_whatsapp(string $pdfPath, string $filename): string
{
    $home = (string) getenv('HOME');
    if ($home === '' && function_exists('posix_geteuid')) {
        $info = posix_getpwuid(posix_geteuid());
        $home = is_array($info) ? (string) ($info['dir'] ?? '') : '';
    }
    $downloads = rtrim($home, '/') . '/Downloads';
    if ($home === '' || !is_dir($downloads)) {
        throw new RuntimeException('The Downloads folder is not available, so WhatsApp cannot read the PDF.');
    }
    $dest = $downloads . '/' . $filename;
    if (!copy($pdfPath, $dest)) {
        throw new RuntimeException('The PDF could not be prepared for WhatsApp.');
    }
    exec('xattr -c ' . escapeshellarg($dest) . ' 2>/dev/null');
    return $dest;
}

function quotation_send_pdf_on_whatsapp(string $pdfPath, string $phone, string $message): void
{
    $sharePath = quotation_copy_for_whatsapp($pdfPath, basename($pdfPath));
    $url = 'whatsapp://send?phone=' . rawurlencode($phone) . '&text=' . rawurlencode($message);
    $script = <<<'APPLESCRIPT'
use framework "AppKit"
use scripting additions

on run argv
	set pdfPath to item 1 of argv
	set chatURL to item 2 of argv
	try
		tell application "System Events" to tell process "Finder" to get role
	on error errMsg
		if errMsg contains "not allowed" or errMsg contains "assistive" then
			error "Allow osascript to control this Mac. Open System Settings, Privacy & Security, Accessibility, turn on osascript, then click Send PDF on WhatsApp again."
		end if
	end try

	set pb to current application's NSPasteboard's generalPasteboard()
	pb's clearContents()
	set wrote to pb's writeObjects:{current application's NSURL's fileURLWithPath:pdfPath}
	if wrote is false then error "The PDF could not be prepared for WhatsApp."

	do shell script "open " & quoted form of chatURL
	delay 2.5

	tell application "System Events"
		if not (exists process "WhatsApp") then error "WhatsApp did not open. Open the WhatsApp app once, then try again."
		tell process "WhatsApp"
			set frontmost to true
			delay 0.6
			set started to false
			repeat with w in windows
				try
					repeat with b in (every button of w)
						set n to ""
						try
							set n to name of b as text
						end try
						if n is "Start chat" or n is "Continue to chat" or n is "Continue" or n is "Message" then
							click b
							set started to true
							exit repeat
						end if
					end repeat
				end try
				if started then exit repeat
			end repeat
			if started then delay 1.2
			keystroke "v" using command down
			delay 1.6
			key code 36
		end tell
	end tell
end run
APPLESCRIPT;

    $scriptPath = dirname($pdfPath) . '/send.applescript';
    file_put_contents($scriptPath, $script);
    $cmd = 'osascript ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($sharePath) . ' ' . escapeshellarg($url);
    $output = [];
    $code = 0;
    exec($cmd . ' 2>&1', $output, $code);
    if ($code !== 0) {
        $detail = trim(implode("\n", $output));
        if ($detail === '') {
            $detail = 'WhatsApp could not send the PDF.';
        }
        if (stripos($detail, 'not allowed') !== false || stripos($detail, 'assistive') !== false) {
            exec('open "x-apple.systempreferences:com.apple.preference.security?Privacy_Accessibility"');
        }
        throw new RuntimeException($detail);
    }
}
