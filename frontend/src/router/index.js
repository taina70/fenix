import { createRouter, createWebHistory } from "vue-router";
const routes = [
  { path: '/', redirect: '/student/exams' },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});
