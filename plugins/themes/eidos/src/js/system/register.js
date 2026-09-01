/**
 * Show/hide privacy consent and review interest fields based
 * on the selected options in the user registration form.
 */

const anyCheckboxChecked = ($checkboxes) => {
  let checked = false
  $checkboxes.forEach($checkbox => {
    if ($checkbox.checked) {
      checked = true
    }
  })
  return checked
}

const toggle = ($element, show) => {
  if (show) {
    $element.removeAttribute('style')
  } else {
    $element.setAttribute('style', 'display: none;')
  }
}

/**
 * Show/hide the review interest fields when the user
 * opts into a reviewer role
 */
const initReviewerInterests = () => {
  const $interests = document.querySelector('input[name="interests"]')?.parentElement
  const $roles = document.querySelectorAll('input[name^="reviewerGroup"]')
  if (!$interests || !$roles.length) {
    return
  }
  $roles.forEach($role => {
    $role.addEventListener('change', () => {
      toggle($interests, anyCheckboxChecked($roles))
    })
  })
  toggle($interests, anyCheckboxChecked($roles))
}

/**
 * Show/hide the privacy consent field when the user
 * has selected a role in a context
 *
 * Only runs when signing up at the site level
 */
const initPrivacyConsent = () => {
  const $consents = document.querySelectorAll('input[name^="privacyConsent[')
  if (!$consents.length) {
    return
  }
  $consents.forEach($consent => {
    const $field = $consent.parentElement
    const $contextRoleCheckboxes = $field.parentElement.querySelectorAll('input[name^="reviewerGroup"],input[name^="readerGroup"]')
    $contextRoleCheckboxes.forEach($checkbox => {
      $checkbox.addEventListener('change', () => {
        toggle($field, anyCheckboxChecked($contextRoleCheckboxes))
      })
    })
    toggle($field, anyCheckboxChecked($contextRoleCheckboxes))
  })
}

const init = () => {
  initReviewerInterests()
  initPrivacyConsent()
}

export default {
  init
}