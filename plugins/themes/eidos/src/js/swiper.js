/**
 * Initialize the Swiper component that controls
 * the highlights on the homepage
 *
 * @see https://swiperjs.com/
 */
import Swiper from 'swiper';
import { A11y, Navigation, Pagination } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

const init = () => {
  const swipers = [...document.querySelectorAll('.swiper')]
    .map($el => {
      const swiper = new Swiper($el, {
        slidesPerView: $el?.dataset?.slidesPerView ?? 'auto',
        modules: [
          A11y,
          Navigation,
          Pagination,
        ],
        ally: {
          prevSlideMessage: $el?.dataset?.allyPrev ?? '',
          nextSlideMessage: $el?.dataset?.allyNext ?? '',
        },
        navigation: {
          nextEl: '.swiper-button-next',
          prevEl: '.swiper-button-prev',
        },
        pagination: {
          el: '.swiper-pagination',
          type: 'bullets',
        }
      });
    })

  return swipers
}

export default {
  init
}