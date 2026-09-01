/**
 * Show part of long texts with a read more button
 * that will show the full text when clicked.
 *
 * Use the reveal component.
 *
 * <x-reveal id="example" height="30">
 *     <x-slot:trigger>
 *         View all
 *     </x-slot>
 *     {{ $exampleData }}
 * </x-reveal>
 *
 * <div data-reveal>
 *   ...
 * </div>
 *
 * Control the height by passing an int to a data-height attribute.
 *
 * <x-reveal id="example" height="30">
 *   ...
 * </x-reveal>
 *
 * This will set the height to 30em, based on the `fontSize` of the
 * element.
 *
 * You can not nest a reveal block inside of another.
 */

const init = () => {
  let i = 0
  const $reveals = document.querySelectorAll('.reveal')
  for (const $reveal of $reveals) {
    const height = parseInt($reveal.dataset?.height ?? '20', 10)
    const fontSize = parseInt(window.getComputedStyle($reveal).getPropertyValue('font-size').replace('px', ''), 10)
    const maxHeight = height * fontSize
    if ($reveal.clientHeight <= maxHeight) {
      $reveal.dataset.open = true
      continue
    }
    const $button = $reveal.querySelector('.reveal-link')
    if (!$button) {
      return
    }
    $button.addEventListener('click', function() {
      $reveal.dataset.open = true
    })
    $reveal.style.maxHeight = `${maxHeight}px`
  }
}

export default {
  init
}