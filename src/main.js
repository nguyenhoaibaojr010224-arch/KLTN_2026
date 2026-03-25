import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import Default from './layout/wrapper/index.vue'
import Empty from './layout/wrapper/empty.vue'
import Customer from './layout/wrapper/customer.vue'
import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap-icons/font/bootstrap-icons.css'
import 'bootstrap/dist/js/bootstrap.bundle.min.js'
import '@fortawesome/fontawesome-free/css/all.min.css'


const app = createApp(App)

app.use(router)
app.component("default-layout", Default);
app.component("empty-layout", Empty);
app.component("customer-layout", Customer);

app.mount("#app")
