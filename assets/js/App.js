//
// Rotate home banners
//
setInterval(() => {
    let img = Math.floor((Math.random() * $('.home-code img').length) + 1);
    $('.home-code img.active').removeClass('active');
    $(`.home-code img:nth-of-type(${img})`).addClass('active');
}, 5000);
