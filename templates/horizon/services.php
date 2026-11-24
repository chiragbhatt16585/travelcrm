<?php
declare(strict_types=1);

require_once __DIR__ . '/layout.php';

$services = [
    ['Flights', 'Domestic & international', 'Routes and fares matched to your tour dates, including group bookings.', ['Several airlines compared', 'Seat preference help', 'Family and corporate groups']],
    ['Cruises', 'Short and long sailings', 'Sea and river cruises with stays arranged before and after the ship.', ['India and overseas ports', 'Cabin and meal choices', 'Shore excursions']],
    ['Cars', 'Private drivers', 'Airport transfers and full-day sightseeing in AC cars or coaches.', ['Pick-up and drop', 'Local and outstation', 'Experienced drivers']],
    ['Hotels', 'Budget to premium', 'Stays chosen for the trip, from homestays to resorts.', ['Deluxe to premium', 'Meal plans', 'Special requests']],
    ['Visas', 'Paperwork', 'Tourist visa files prepared against the current embassy checklist.', ['Document list', 'Form filling', 'Appointment help']],
    ['Tours', 'Fixed or private', 'Ready packages and trips rewritten around your dates.', ['Private or group', 'Families and couples', 'Support on the road']],
];

hz_head('Services — ' . site_profile()['company_name']);
hz_header('services');
?>
<section class="hz-section">
  <div class="hz-wrap">
    <div class="hz-section-head">
      <div>
        <div class="hz-kicker">Services</div>
        <h2>One desk for the whole trip.</h2>
      </div>
      <p>Flights, visas, hotels, cars, and cruises, arranged together so you are not chasing five vendors.</p>
    </div>
    <div class="hz-services">
      <?php foreach ($services as [$title, $tag, $text, $points]): ?>
        <article class="hz-service">
          <span><?= e($tag) ?></span>
          <h3><?= e($title) ?></h3>
          <p><?= e($text) ?></p>
          <ul>
            <?php foreach ($points as $point): ?><li><?= e($point) ?></li><?php endforeach; ?>
          </ul>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php hz_footer(); ?>
