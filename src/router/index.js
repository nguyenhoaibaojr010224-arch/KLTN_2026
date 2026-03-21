import { createRouter, createWebHistory } from "vue-router"; // cài vue-router: npm install vue-router@next --save

const routes = [
    {
        path : '/aaa',
        component: ()=>import('../layout/components/test.vue')
        // component: ()=>import('../layout/components/test.vue')
    },
    {
        path : '/aaa',
        component: ()=>import('../layout/components/test.vue')
        // component: ()=>import('../layout/components/test.vue')
    },
]

const router = createRouter({
    history: createWebHistory(),
    routes: routes
})

export default router