/**
 * Custom JS for the theme
 */
import swiper from './js/swiper'
import mobileMenu from './js/mobile-menu'
import reveal from './js/reveal'
import tabTrigger from './js/tab-trigger'

/**
 * Custom CSS for the theme
 *
 * @see https://vite.dev/guide/features#css
 */
import './main.css'

/**
 * Run our custom JS when the page is fully loaded.
 */
document.addEventListener('DOMContentLoaded',function() {
  swiper.init()
  mobileMenu.init()
  reveal.init()
  tabTrigger.init()
})