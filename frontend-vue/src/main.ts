import './assets/main.css'
import 'primeicons/primeicons.css'
import 'vue-toastification/dist/index.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import Toast from 'vue-toastification'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)
app.use(Toast, {
  position: 'bottom-right',
  hideProgressBar: true,
  pauseOnHover: false,
  closeOnClick: true,
  draggable: false,
  dangerouslyHTMLString: false,
  showCloseButtonOnHover: false,
  timeout: 2000,
})

app.mount('#app')
