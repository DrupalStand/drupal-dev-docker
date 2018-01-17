jQuery(function ($) {
  $('.field--name-field-carousel-slide').slick({
    dots: true,
    arrows: true,
    infinite: true,
    speed: 300,
    slidesToShow: 1,
    slidesToScroll: 1,
  });
  $('.page-node-type-event a.usa-nav-link:contains("Calendar")').addClass('usa-current');
});