import * as vue from 'vue';
import UserComment from '@/components/UserComment/UserComment.vue';
import * as useLocalize from '@/composables/useLocalize.js';
import {createApp} from 'vue';

import {createPinia} from 'pinia';

const app = createApp();

const pinia = createPinia();
app.use(pinia)

app.component("UserComment", UserComment);
app.mount("#UserComment");

window.pkp = {
	modules: {
		vue,
		useLocalize,
	}
};

