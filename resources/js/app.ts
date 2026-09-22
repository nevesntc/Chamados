import '../css/app.css';
import { createApp, h, type DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import AppLayout from './Layouts/AppLayout.vue';
const pages = import.meta.glob<{ default: DefineComponent }>('./Pages/**/*.vue', { eager: true });
createInertiaApp({
  title: (title) => (title ? `${title} · Central` : 'Central de Chamados'),
  resolve: (name) => {
    const page = pages[`./Pages/${name}.vue`];
    const component = page.default;
    component.layout = AppLayout;
    return component;
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .mount(el);
  },
  progress: { color: '#4f46e5' },
});
