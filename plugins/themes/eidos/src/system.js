/**
 * Assets for the system layout
 */
import './system.css'

/**
 * Custom JS for the theme
 */
import register from './js/system/register'

/**
 * Run JS when the page is fully loaded.
 */
document.addEventListener('DOMContentLoaded',function() {
  register.init()
})