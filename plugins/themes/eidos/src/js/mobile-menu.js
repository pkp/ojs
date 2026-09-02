/**
 * Toggles the mobile navigation menu on/off
 *
 * Only handles the aria- attributes. The visible changes need to be
 * added in CSS.
 *
 * Usage:
 *
 * <button data-mobile-menu="example-id">
 *   Toggle
 * </button>
 * <div id="example-id">
 *   ...
 * </div>
 *
 * Use the following selector to style the button when open:
 *
 * [data-mobile-menu][aria-expanded="true"]
 *
 * Use the following selector to style the panel when open:
 *
 * [data-open="true"]
 */
import debounce from "debounce"

const overflowHiddenClass = 'overflow-hidden'

const set = ($button, $target, $focusable, state) => {
  if (state == $target.dataset.open) {
    return
  }
  $button.setAttribute('aria-expanded', state)
  $target.dataset.open = state
  $target.inert = !state
  if (state) {
    document.body.className = ` ${overflowHiddenClass}`
    $focusable[0].focus()
  } else {
    document.body.className = document.body.className.replace(` ${overflowHiddenClass}`, '')
    $button.focus()
  }
}

const init = () => {
  const $button = document.querySelector('[data-mobile-menu]')
  if (!$button) {
    return
  }
  const target = $button.dataset?.mobileMenu
  if (!target) {
    return
  }
  const $target = document.querySelector(target)
  $target.tabIndex = -1

  const $focusable = $target.querySelectorAll(
    `a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), details, [tabindex]:not([tabindex="-1"])`
  );

  const $close = $target.querySelector('[data-mobile-menu-close]')
  $close.addEventListener('click', () => {
    set($button, $target, $focusable, false)
  })

  $button.id = `${target.replace('#', '')}-button`
  $button.setAttribute('aria-controls', target)
  $button.setAttribute('aria-expanded', false)
  set($button, $target, $focusable, $button.getAttribute('aria-expanded') === 'true')

  $button.addEventListener('click', function() {
    set($button, $target, $focusable, $button.getAttribute('aria-expanded') !== 'true')
  })

  $target.addEventListener('focusout', function(e) {
    if (!e?.relatedTarget || !$target.contains(e?.relatedTarget)) {
      if (e.target === $close) {
        $focusable[0]?.focus()
      } else {
        $close.focus()
      }
    }
  })

  document.addEventListener('click', function(e) {
    if ($target.dataset.open === 'true' && !$button.contains(e.target) && !$target.contains(e.target)) {
      set($button, $target, $focusable, false)
    }
  })

  addEventListener('resize', debounce(() => {
    if (document.body.clientWidth >= 1200) {
      set($button, $target, $focusable, false)
    }
  }, 300))
}

export default {
  init
}