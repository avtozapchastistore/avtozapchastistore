<?php
session_start();
$pageTitle = 'О нас';
$extraCss = ['css/about.css']; // чтобы header.php подключил стили этой страницы (см. правку ниже)
require __DIR__ . '/header.php';
?>

<main class="container about-page">
  <section class="about-hero">
    <h1>О нас</h1>
    <p>Мы занимаемся продажей автозапчастей и расходников. Работаем с надёжными поставщиками, помогаем подобрать детали по VIN и обеспечиваем быструю доставку по всей России.</p>
  </section>

  <section class="contacts">
    <h2 class="section-title">Контакты</h2>

    <div class="contacts-grid">
      <div class="card contact-card">
        <div class="contact-title">Адрес</div>
        <div class="contact-content">г. Москва, ул. Примерная, 10</div>
      </div>

      <div class="card contact-card">
        <div class="contact-title">Телефон</div>
        <div class="contact-content">
          <a href="tel:+79990000000">+7 (999) 000-00-00</a>
        </div>
      </div>

      <div class="card contact-card">
        <div class="contact-title">E-mail</div>
        <div class="contact-content">
          <a href="mailto:info@example.ru">info@example.ru</a>
        </div>
      </div>

      <div class="card contact-card">
        <div class="contact-title">График работы</div>
        <div class="contact-content">Пн-Пт: 9:00–19:00, Сб: 10:00–16:00, Вс: выходной</div>
      </div>
    </div>
  </section>

  <section class="map-block">
    <h2 class="section-title">Мы на карте</h2>
    <div id="yaMap" class="map" role="region" aria-label="Карта расположения магазина"></div>
    <div class="map-hint">Если карта не загрузилась — проверьте API-ключ Яндекс Карт.</div>
  </section>
</main>

<!-- Яндекс.Карты API. Подставь свой ключ вместо YOUR_YANDEX_API_KEY -->
<script src="https://api-maps.yandex.ru/2.1/?apikey=YOUR_YANDEX_API_KEY&lang=ru_RU" defer></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (window.ymaps) { ymaps.ready(initMap); }
    else {
      const t = setInterval(() => { if (window.ymaps) { clearInterval(t); ymaps.ready(initMap); } }, 200);
      setTimeout(() => clearInterval(t), 8000);
    }
    function initMap() {
      const coords = [55.751244, 37.618423]; // замени на координаты магазина
      const map = new ymaps.Map('yaMap', { center: coords, zoom: 14, controls: ['zoomControl','fullscreenControl'] });
      const placemark = new ymaps.Placemark(coords, {
        balloonContentHeader: 'Магазин автозапчастей',
        balloonContentBody: 'г. Москва, ул. Примерная, 10<br>Тел.: +7 (999) 000-00-00',
        hintContent: 'Мы здесь'
      }, { preset: 'islands#redIcon' });
      map.geoObjects.add(placemark);
      window.addEventListener('resize', () => map.container.fitToViewport());
    }
  });
</script>

<?php require __DIR__ . '/footer.php'; ?>
