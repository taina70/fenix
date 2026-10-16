import { ref, computed } from "vue";

// Recupera do localStorage ou assume o aluno por padrão
const currentUser = ref(
  JSON.parse(localStorage.getItem("user")) || {
    id: 2,
    name: "Maria Oliveira",
    role: "student",
  },
);

export function useAuth() {
  const setUser = (user) => {
    currentUser.value = user;
    localStorage.setItem("user", JSON.stringify(user));
  };

  const switchUser = (role) => {
    if (role === "teacher") {
      setUser({ id: 1, name: "Professor Silva", role: "teacher" });
    } else {
      setUser({ id: 2, name: "Maria Oliveira", role: "student" });
    }
  };

  const isStudent = computed(() => currentUser.value?.role === "student");
  const isTeacher = computed(() => currentUser.value?.role === "teacher");

  return { currentUser, isStudent, isTeacher, switchUser };
}
