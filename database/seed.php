<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo = db();

$adminHash = password_hash('admin123', PASSWORD_DEFAULT);
$pdo->exec('DELETE FROM package_items');
$pdo->exec('DELETE FROM package_hotels');
$pdo->exec('DELETE FROM package_itinerary');
$pdo->exec('DELETE FROM packages');
$pdo->exec('DELETE FROM destinations');
$pdo->exec('DELETE FROM admins');

$pdo->prepare('INSERT INTO admins (username, password_hash, name) VALUES (?, ?, ?)')
    ->execute(['admin', $adminHash, 'Travel World Admin']);

$destinations = [
    [
        'name' => 'Kerala',
        'slug' => 'kerala',
        'tagline' => 'Munnar • Alleppey • Kochi',
        'description' => 'Lush green hills, serene backwaters and coastal charms make Kerala one of India’s most relaxing family and honeymoon destinations.',
        'hero_image' => 'assets/images/2.jpg',
        'type' => 'domestic',
        'duration_label' => '5N / 6D',
        'starting_price' => 18499,
        'meta_left' => '3 hotel options • Private cab',
        'meta_right' => 'Family, Honeymoon',
        'sort_order' => 1,
    ],
    [
        'name' => 'Himachal',
        'slug' => 'himachal',
        'tagline' => 'Shimla • Manali • Solang Valley',
        'description' => 'Snow peaks, pine forests and hill towns make Himachal an all-season favourite for families, honeymooners and adventure seekers.',
        'hero_image' => 'assets/images/1.jpg',
        'type' => 'domestic',
        'duration_label' => '6N / 7D',
        'starting_price' => 15999,
        'meta_left' => 'Volvo / Private transfers',
        'meta_right' => 'Snow, Adventure',
        'sort_order' => 2,
    ],
    [
        'name' => 'Andaman',
        'slug' => 'andaman',
        'tagline' => 'Port Blair • Havelock • Neil Island',
        'description' => 'Crystal waters, white beaches and island hopping make Andaman a perfect tropical escape.',
        'hero_image' => null,
        'type' => 'domestic',
        'duration_label' => '4N / 5D',
        'starting_price' => 22999,
        'meta_left' => 'Speedboat • Beach activities',
        'meta_right' => 'Water Sports',
        'sort_order' => 3,
    ],
    [
        'name' => 'Goa',
        'slug' => 'goa',
        'tagline' => 'North & South Goa Highlights',
        'description' => 'Beaches, nightlife and Portuguese charm — ideal for couples and friends.',
        'hero_image' => null,
        'type' => 'domestic',
        'duration_label' => '3N / 4D',
        'starting_price' => 9999,
        'meta_left' => 'Beach resorts • Nightlife',
        'meta_right' => 'Couple, Friends',
        'sort_order' => 4,
    ],
    [
        'name' => 'Bali',
        'slug' => 'bali',
        'tagline' => 'Kuta • Ubud • Nusa Penida',
        'description' => 'Combination of beaches, rice terraces and island excursions – ideal for honeymooners and small groups.',
        'hero_image' => 'assets/images/3.jpg',
        'type' => 'international',
        'duration_label' => '5N / 6D',
        'starting_price' => 58999,
        'meta_left' => 'Pool villa • Island tours',
        'meta_right' => 'Honeymoon, Friends',
        'sort_order' => 1,
    ],
    [
        'name' => 'Maldives',
        'slug' => 'maldives',
        'tagline' => 'Water Villa & Lagoon Experience',
        'description' => 'Overwater villas and turquoise lagoons for a luxury honeymoon escape.',
        'hero_image' => null,
        'type' => 'international',
        'duration_label' => '4N / 5D',
        'starting_price' => 92499,
        'meta_left' => 'All inclusive meal plans',
        'meta_right' => 'Luxury, Honeymoon',
        'sort_order' => 2,
    ],
    [
        'name' => 'Dubai',
        'slug' => 'dubai',
        'tagline' => 'City, Desert Safari & Cruise',
        'description' => 'Modern skyline, desert adventures and shopping — a family favourite.',
        'hero_image' => null,
        'type' => 'international',
        'duration_label' => '4N / 5D',
        'starting_price' => 39999,
        'meta_left' => 'Dubai city • Dhow cruise',
        'meta_right' => 'Family, Shopping',
        'sort_order' => 3,
    ],
    [
        'name' => 'Thailand',
        'slug' => 'thailand',
        'tagline' => 'Bangkok • Pattaya • Phuket',
        'description' => 'Temples, beaches and nightlife across Thailand’s top cities.',
        'hero_image' => null,
        'type' => 'international',
        'duration_label' => '5N / 6D',
        'starting_price' => 34499,
        'meta_left' => 'Island hopping, Nightlife',
        'meta_right' => 'Friends, Groups',
        'sort_order' => 4,
    ],
];

$destStmt = $pdo->prepare(
    'INSERT INTO destinations (name, slug, tagline, description, hero_image, type, duration_label, starting_price, meta_left, meta_right, sort_order, is_active)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,1)'
);

$destIds = [];
foreach ($destinations as $d) {
    $destStmt->execute([
        $d['name'], $d['slug'], $d['tagline'], $d['description'], $d['hero_image'],
        $d['type'], $d['duration_label'], $d['starting_price'], $d['meta_left'], $d['meta_right'], $d['sort_order'],
    ]);
    $destIds[$d['slug']] = (int) $pdo->lastInsertId();
}

$packages = [
    [
        'destination' => 'kerala',
        'title' => 'Classic Kerala Family Getaway',
        'nights' => 5, 'days' => 6,
        'route' => 'Kochi – Munnar – Thekkady – Alleppey',
        'package_type' => 'Family • Sightseeing',
        'starting_price' => 18499,
        'hero_image' => 'assets/images/2.jpg',
        'itinerary' => [
            ['Day 1', 'Arrival at Kochi, transfer to Munnar. En route waterfalls & scenic viewpoints. Overnight in Munnar.'],
            ['Day 2', 'Munnar full day sightseeing – Tea gardens, Eravikulam National Park, Mattupetty Dam, Echo Point.'],
            ['Day 3', 'Drive to Thekkady. Spice plantation visit, evening optional boating at Periyar Lake.'],
            ['Day 4', 'Drive to Alleppey. Check-in to houseboat / resort, backwater cruise with all meals.'],
            ['Day 5', 'Free time in Alleppey / nearby beach visit. Optional village walk.'],
            ['Day 6', 'Checkout and transfer to Kochi airport / railway station for onward journey.'],
        ],
        'hotels' => [
            ['Cochin', 'Deluxe', 'Abad Fort or Similar Category'],
            ['Cochin', 'Super Deluxe', 'Quality Airport Hotel or Travancore Court by Spree or Similar Category'],
            ['Cochin', 'Premium', 'Airlink Castle Hotel or Diana Heights Hotel or Similar Category'],
            ['Munnar', 'Deluxe', 'Spice Country Resort or Dream Catcher Plantation Resort or Green Ridge Holiday or Similar Category'],
            ['Munnar', 'Super Deluxe', 'Hotel C7 or Grand Plaza or Similar Category'],
            ['Munnar', 'Premium', 'Munnar Queen or Rivulet Resort or Similar Category'],
            ['Thekkady', 'Deluxe', 'Hotel Seasons Thekkady or Hotel Sandra Palace or Similar Category'],
            ['Thekkady', 'Super Deluxe', 'Peppervine or Tree Top Resort or Similar Category'],
            ['Thekkady', 'Premium', 'Springdale Heritage Resort or Holiday Vista Resort or Similar Category'],
        ],
        'inclusions' => [
            'Accommodation on twin sharing basis as per itinerary.',
            'Daily breakfast at hotel (all meals on houseboat).',
            'Private AC cab for all transfers & sightseeing.',
            'Toll, parking, driver allowance, and state taxes.',
            'All applicable hotel taxes as of date.',
        ],
        'exclusions' => [
            'Flight / train tickets.',
            'Entrance fees at monuments, parks and activities.',
            'Personal expenses such as tips, laundry, telephone calls.',
            'Anything not mentioned in the inclusions.',
            '5% GST or any new government taxes.',
        ],
    ],
    [
        'destination' => 'kerala',
        'title' => 'Romantic Kerala Honeymoon',
        'nights' => 4, 'days' => 5,
        'route' => 'Munnar – Alleppey Houseboat',
        'package_type' => 'Honeymoon • Private Cab',
        'starting_price' => 21999,
        'hero_image' => 'assets/images/2.jpg',
        'itinerary' => [
            ['Day 1', 'Arrive Kochi and transfer to Munnar. Evening leisure.'],
            ['Day 2', 'Munnar sightseeing – tea gardens and viewpoints.'],
            ['Day 3', 'Drive to Alleppey and check-in to houseboat.'],
            ['Day 4', 'Backwater cruise and village experience.'],
            ['Day 5', 'Checkout and transfer to Kochi for departure.'],
        ],
        'hotels' => [
            ['Munnar', 'Premium', 'Munnar Queen or Similar Category'],
            ['Alleppey', 'Premium', 'Deluxe Houseboat or Similar Category'],
        ],
        'inclusions' => ['Hotel stay with breakfast', 'Private transfers', 'Houseboat with meals'],
        'exclusions' => ['Airfare', 'Personal expenses', 'GST'],
    ],
    [
        'destination' => 'kerala',
        'title' => 'Premium Kerala with Kovalam',
        'nights' => 6, 'days' => 7,
        'route' => 'Kochi – Munnar – Thekkady – Alleppey – Kovalam',
        'package_type' => 'Premium • Beach + Hills',
        'starting_price' => 27499,
        'hero_image' => 'assets/images/2.jpg',
        'itinerary' => [
            ['Day 1', 'Arrive Kochi, transfer to Munnar.'],
            ['Day 2', 'Munnar sightseeing.'],
            ['Day 3', 'Drive to Thekkady, spice plantation.'],
            ['Day 4', 'Drive to Alleppey houseboat.'],
            ['Day 5', 'Transfer to Kovalam beach.'],
            ['Day 6', 'Kovalam leisure day.'],
            ['Day 7', 'Departure transfer.'],
        ],
        'hotels' => [
            ['Munnar', 'Premium', 'Rivulet Resort or Similar'],
            ['Kovalam', 'Premium', 'Beach Resort or Similar'],
        ],
        'inclusions' => ['Premium hotels', 'Private cab', 'Breakfast'],
        'exclusions' => ['Flights', 'Activities', 'GST'],
    ],
    [
        'destination' => 'himachal',
        'title' => 'Shimla Manali Volvo Package',
        'nights' => 6, 'days' => 7,
        'route' => 'Delhi – Shimla – Manali – Delhi',
        'package_type' => 'Budget • Volvo',
        'starting_price' => 15999,
        'hero_image' => 'assets/images/1.jpg',
        'itinerary' => [
            ['Day 1', 'Departure from Delhi by overnight Volvo bus to Shimla.'],
            ['Day 2', 'Arrive Shimla, local sightseeing – Mall Road, Ridge, Jakhoo Temple.'],
            ['Day 3', 'Excursion to Kufri & nearby viewpoints. Overnight in Shimla.'],
            ['Day 4', 'Drive to Manali. Evening free for leisure.'],
            ['Day 5', 'Manali local sightseeing – Hadimba Temple, Club House, Tibetan Monastery.'],
            ['Day 6', 'Excursion to Solang Valley / Snow point (as per season).'],
            ['Day 7', 'Checkout and Volvo back to Delhi.'],
        ],
        'hotels' => [
            ['Shimla', 'Deluxe', 'Snow View Hotel or Similar Category'],
            ['Shimla', 'Super Deluxe', 'Hotel Willow Banks or Similar Category'],
            ['Shimla', 'Premium', 'Radisson Jass Shimla or Similar Category'],
            ['Manali', 'Deluxe', 'Apple Valley or Similar Category'],
            ['Manali', 'Super Deluxe', 'Honeymoon Inn or Similar Category'],
            ['Manali', 'Premium', 'River Country Resort or Similar Category'],
        ],
        'inclusions' => [
            'Accommodation in selected category of hotel.',
            'Daily breakfast and dinner at hotel.',
            'Volvo bus tickets Delhi–Shimla, Manali–Delhi.',
            'Private cab / sharing cab for sightseeing (as per plan).',
            'All tolls, parking, and driver allowances.',
        ],
        'exclusions' => [
            'Any airfare or train tickets.',
            'Heater charges at hotel.',
            'Adventure activities like paragliding, skiing, etc.',
            'Entry fees for monuments and sightseeing points.',
            'Goods & Services Tax and anything not mentioned.',
        ],
    ],
    [
        'destination' => 'himachal',
        'title' => 'Himachal Honeymoon Special',
        'nights' => 5, 'days' => 6,
        'route' => 'Shimla – Manali',
        'package_type' => 'Honeymoon • Private Cab',
        'starting_price' => 19999,
        'hero_image' => 'assets/images/1.jpg',
        'itinerary' => [
            ['Day 1', 'Arrive Shimla, evening Mall Road walk.'],
            ['Day 2', 'Shimla sightseeing including Kufri.'],
            ['Day 3', 'Drive to Manali.'],
            ['Day 4', 'Manali local sightseeing.'],
            ['Day 5', 'Solang Valley excursion.'],
            ['Day 6', 'Departure.'],
        ],
        'hotels' => [
            ['Shimla', 'Premium', 'Willow Banks or Similar'],
            ['Manali', 'Premium', 'Honeymoon Inn or Similar'],
        ],
        'inclusions' => ['Hotel with breakfast & dinner', 'Private cab', 'Sightseeing'],
        'exclusions' => ['Flights/trains', 'Adventure activities', 'GST'],
    ],
    [
        'destination' => 'bali',
        'title' => 'Best of Bali with Nusa Penida',
        'nights' => 5, 'days' => 6,
        'route' => 'Kuta – Ubud – Nusa Penida',
        'package_type' => 'Honeymoon • Private Tours',
        'starting_price' => 58999,
        'hero_image' => 'assets/images/3.jpg',
        'itinerary' => [
            ['Day 1', 'Arrival at Bali, transfer to Kuta hotel. Evening free or optional sunset dinner cruise.'],
            ['Day 2', 'Full day Kuta & South Bali tour – beaches, Uluwatu temple, cultural show.'],
            ['Day 3', 'Transfer to Ubud via Tegenungan waterfalls, rice terraces & coffee plantation.'],
            ['Day 4', 'Full day Nusa Penida island tour with lunch (shared / private as per package).'],
            ['Day 5', 'Free day for shopping or optional Bali swing / spa.'],
            ['Day 6', 'Checkout and transfer to airport for onward flight.'],
        ],
        'hotels' => [
            ['Kuta', 'Deluxe', 'Kuta Central Park or Similar Category'],
            ['Kuta', 'Super Deluxe', 'The Kana Kuta Hotel or Similar Category'],
            ['Kuta', 'Premium', 'The Anvaya Beach Resort or Similar Category'],
            ['Ubud', 'Deluxe', 'Pertiwi Resort & Spa or Similar Category'],
            ['Ubud', 'Super Deluxe', 'Alaya Resort Ubud or Similar Category'],
            ['Ubud', 'Premium', 'Private Pool Villa – Kamandalu or Similar Category'],
        ],
        'inclusions' => [
            'Accommodation with breakfast in selected hotel / villa.',
            'Return airport transfers on private basis.',
            'Full day tours as per itinerary (seat-in-coach or private).',
            'All entrance fees mentioned in itinerary.',
            'Visa on arrival guidance (if applicable) and all local taxes.',
        ],
        'exclusions' => [
            'International airfares.',
            'Travel insurance.',
            'Any personal expenses, tips and room service.',
            'Optional tours not mentioned in the inclusions.',
            'Anything not mentioned in inclusions and 5% GST.',
        ],
    ],
    [
        'destination' => 'bali',
        'title' => 'Bali Friends Getaway',
        'nights' => 4, 'days' => 5,
        'route' => 'Kuta – Ubud',
        'package_type' => 'Friends • Activities',
        'starting_price' => 46499,
        'hero_image' => 'assets/images/3.jpg',
        'itinerary' => [
            ['Day 1', 'Arrive Bali, transfer to Kuta.'],
            ['Day 2', 'South Bali sightseeing.'],
            ['Day 3', 'Transfer to Ubud with waterfalls.'],
            ['Day 4', 'Ubud leisure / optional activities.'],
            ['Day 5', 'Airport transfer.'],
        ],
        'hotels' => [
            ['Kuta', 'Deluxe', 'Kuta Central Park or Similar'],
            ['Ubud', 'Deluxe', 'Pertiwi Resort or Similar'],
        ],
        'inclusions' => ['Hotels with breakfast', 'Airport transfers', 'Selected tours'],
        'exclusions' => ['Flights', 'Visa fees', 'GST'],
    ],
];

$pkgStmt = $pdo->prepare(
    'INSERT INTO packages (destination_id, title, nights, days, route, package_type, starting_price, hero_image, sort_order, is_active)
     VALUES (?,?,?,?,?,?,?,?,?,1)'
);

foreach ($packages as $i => $pkg) {
    $pkgStmt->execute([
        $destIds[$pkg['destination']],
        $pkg['title'],
        $pkg['nights'],
        $pkg['days'],
        $pkg['route'],
        $pkg['package_type'],
        $pkg['starting_price'],
        $pkg['hero_image'],
        $i + 1,
    ]);
    $packageId = (int) $pdo->lastInsertId();

    $itinerary = [];
    foreach ($pkg['itinerary'] as $row) {
        $itinerary[] = ['day_label' => $row[0], 'description' => $row[1]];
    }
    $hotels = [];
    foreach ($pkg['hotels'] as $row) {
        $hotels[] = [
            'location_name' => $row[0],
            'category' => $row[1],
            'hotel_name' => $row[2],
        ];
    }

    save_package_children($packageId, [
        'itinerary' => $itinerary,
        'hotels' => $hotels,
        'inclusions' => $pkg['inclusions'],
        'exclusions' => $pkg['exclusions'],
    ]);
}

echo "Seed completed.\n";
echo "Admin login: admin / admin123\n";
echo "Destinations: " . count($destIds) . "\n";
echo "Packages: " . count($packages) . "\n";
