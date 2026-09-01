/**
 * Allow external buttons to act as triggers for the
 * `<pkp-tab-root>` components.
 *
 * Open a tab from any button or interactive element on
 * the page by adding data attributes.
 *
 * <button data-tab-group="section" data-tab="comments">
 *  ...
 * </button>
 *
 * This should only be used when the trigger element is
 * outside of `<pkp-tab-root>`. When the trigger is
 * within the tab root, use the `<pkp-tab-trigger>`
 * component.
 *
 * @see lib/ui-library/src/frontend/components/PkpTab/
 */

const dispatch = (group, tab) => {
  window.dispatchEvent(new CustomEvent('pkp.opentab', {detail: {group, tab}}))
}

const init = () => {
  const $triggers = document.querySelectorAll('[data-tab-group][data-tab]')
  for (const $trigger of $triggers) {
    $trigger.addEventListener('click', (e) => {
      e.preventDefault()
      dispatch($trigger.dataset.tabGroup, $trigger.dataset.tab)
    })
  }
}

export default {
  init
}