import { createRouter, createWebHistory } from "vue-router";

// import ExamListView from "../views/student/ExamListView.vue";
// import ExamTakeView from "../views/student/ExamTakeView.vue";
// import ExamResultView from "../views/student/ExamResultView.vue";

import DashboardView from "../views/professor/DashboardView.vue";
import ExamCreateView from "../views/professor/ExamCreateView.vue";
import ExamManageView from "../views/professor/ExamManageView.vue";

const routes = [
  { path: "/", redirect: "/student/exams" },

  // { path: "/student/exams", name: "student.exams", component: ExamListView },
  // {
  //   path: "/student/exams/:id/take",
  //   name: "student.exams.take",
  //   component: ExamTakeView,
  // },
  // {
  //   path: "/student/exams/:id/result",
  //   name: "student.exams.result",
  //   component: ExamResultView,
  // },

  {
    path: "/professor/dashboard",
    name: "professor.dashboard",
    component: DashboardView,
  },
  {
    path: "/professor/exams/create",
    name: "professor.exams.create",
    component: ExamCreateView,
  },
  {
    path: "/professor/exams/manage",
    name: "professor.exams.manage",
    component: ExamManageView,
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
