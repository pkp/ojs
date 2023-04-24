/**
 * @defgroup plugins_pubIds_urn_js
 */
/**
 * @file plugins/pubIds/urn/js/FieldPubIdUrn.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief A Vue.js component for URN field, that is used for pattern suffixes and that considers check number.
 */

pkp.Vue.component('field-pub-id-urn', {
    name: 'FieldPubIdUrn',
    extends: pkp.Vue.component('field-pub-id'),
    props: {
        applyCheckNumber: {
            type: Boolean,
            required: true
        }
    },
    methods: {
        generateId() {
            var id = pkp.Vue.component('field-pub-id').options.methods['generateId'].apply(this);
            return this.applyCheckNumber
                ? id + $.pkp.plugins.generic.urn.getCheckNumber(id, this.prefix)
                : id;
        }
    },
});