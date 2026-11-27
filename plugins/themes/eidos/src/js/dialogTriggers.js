/**
 * Initialize buttons that open a PkpDialog component
 */
const init = () => {
  const $dialogTriggers = [...document.querySelectorAll('[data-cite]')]
  if (!$dialogTriggers.length) {
    return $dialogTriggers
  }

  const {usePkpModal} = pkp.modules.usePkpModal
  const {openDialog} = usePkpModal()

  $dialogTriggers.map($el => {
      try {
        const data = JSON.parse($el?.dataset?.cite)
        if (!data) {
          return null
        }
        $el.addEventListener('click', () => {
          openDialog(data)
        })
      } catch (e) {
        return null
      }
      return $el
    })
    .filter($button => $button)

  return $dialogTriggers
}

export default {
  init
}