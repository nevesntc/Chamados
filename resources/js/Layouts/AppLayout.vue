<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
  Ticket,
  Layers3,
  ArrowUpRight,
  CheckCircle2,
  ChevronRight,
  PanelLeftClose,
  PanelLeftOpen,
  LayoutDashboard,
  UsersRound,
  UserRound,
  LogOut,
} from '@lucide/vue';
import { ref, computed, nextTick } from 'vue';
const page = usePage<{
  flash: { success?: string };
  auth: { user: { name: string; email: string }; workspace: { name: string } };
}>();
const path = computed(() => page.url.split('?')[0]);
const section = computed(() =>
  path.value.startsWith('/workspace/chamados')
    ? 'Chamados'
    : path.value.startsWith('/workspace/equipe')
      ? 'Equipe'
      : path.value.startsWith('/workspace/perfil')
        ? 'Perfil'
        : 'Painel',
);
function logout() {
  router.post('/sair');
}
const menuOpen = ref(false);
const navigation = ref<HTMLElement | null>(null);
const menuButton = ref<HTMLButtonElement | null>(null);
async function closeMenu() {
  menuOpen.value = false;
  await nextTick();
  menuButton.value?.focus();
}
async function toggleMenu() {
  if (menuOpen.value) return closeMenu();
  menuOpen.value = true;
  await nextTick();
  navigation.value?.querySelector<HTMLAnchorElement>('a')?.focus();
}
const success = computed(() => page.props.flash?.success);
</script>

<template>
  <div class="app-shell">
    <a class="skip-link" href="#main-content">Pular para o conteúdo</a>
    <aside
      id="workspace-navigation"
      ref="navigation"
      class="sidebar"
      :class="{ 'sidebar-open': menuOpen }"
      @keydown.esc="closeMenu"
    >
      <Link href="/workspace" class="brand" @click="menuOpen = false">
        <span class="brand-icon"><Ticket :size="22" /></span>
        <span>
          central
          <span class="brand-dot">.</span>
        </span>
      </Link>
      <div class="workspace">
        <span class="workspace-symbol">
          {{ page.props.auth.workspace.name.charAt(0).toUpperCase() }}
        </span>
        <div>
          <strong>{{ page.props.auth.workspace.name }}</strong>
          <small>Espaço privado da equipe</small>
        </div>
        <span class="workspace-dot"></span>
      </div>
      <p class="nav-label">WORKSPACE</p>
      <nav aria-label="Navegação principal">
        <Link
          href="/workspace"
          class="nav-item"
          :prefetch="['hover', 'click']"
          cache-for="30s"
          :class="{ active: path === '/workspace' }"
          @click="menuOpen = false"
        >
          <LayoutDashboard :size="18" />
          Painel
          <ChevronRight :size="15" />
        </Link>
        <Link
          href="/workspace/chamados"
          class="nav-item"
          :prefetch="['hover', 'click']"
          cache-for="30s"
          :class="{ active: path.startsWith('/workspace/chamados') }"
          @click="menuOpen = false"
        >
          <Layers3 :size="18" />
          Chamados
          <ChevronRight :size="15" />
        </Link>
        <Link
          href="/workspace/equipe"
          class="nav-item"
          :prefetch="['hover', 'click']"
          cache-for="30s"
          :class="{ active: path.startsWith('/workspace/equipe') }"
          @click="menuOpen = false"
        >
          <UsersRound :size="18" />
          Equipe
          <ChevronRight :size="15" />
        </Link>
        <Link
          href="/workspace/perfil"
          class="nav-item"
          :prefetch="['hover', 'click']"
          cache-for="30s"
          :class="{ active: path.startsWith('/workspace/perfil') }"
          @click="menuOpen = false"
        >
          <UserRound :size="18" />
          Perfil
          <ChevronRight :size="15" />
        </Link>
      </nav>
      <div class="sidebar-note">
        <span class="note-icon"><CheckCircle2 :size="19" /></span>
        <strong>Cada pedido, um próximo passo.</strong>
        <p>Organize as solicitações e mantenha sua equipe na mesma página.</p>
        <Link href="/workspace/chamados/create" @click="menuOpen = false">
          Abrir um chamado
          <ArrowUpRight :size="15" />
        </Link>
      </div>
      <div class="sidebar-footer user-footer">
        <span class="avatar avatar-small avatar-0">
          {{ page.props.auth.user.name.charAt(0).toUpperCase() }}
        </span>
        <span>{{ page.props.auth.user.name }}</span>
        <button type="button" class="icon-button" aria-label="Sair" @click="logout">
          <LogOut :size="16" />
        </button>
      </div>
    </aside>
    <button
      v-if="menuOpen"
      class="sidebar-overlay"
      aria-label="Fechar navegação"
      @click="menuOpen = false"
    ></button>
    <div class="workspace-main">
      <header class="topbar">
        <div class="breadcrumb">
          <button
            ref="menuButton"
            class="mobile-menu icon-button"
            aria-controls="workspace-navigation"
            aria-label="Alternar navegação"
            :aria-expanded="menuOpen"
            @click="toggleMenu"
          >
            <PanelLeftClose v-if="menuOpen" :size="20" />
            <PanelLeftOpen v-else :size="20" />
          </button>
          <span>Workspace</span>
          <ChevronRight :size="14" />
          <strong>{{ section }}</strong>
        </div>
        <div class="topbar-label">
          <span class="online-dot"></span>
          {{ page.props.auth.workspace.name }}
        </div>
      </header>
      <main id="main-content" tabindex="-1">
        <div v-if="success" :key="success" class="flash-message" role="status">
          <CheckCircle2 :size="18" />
          {{ success }}
        </div>
        <slot />
      </main>
      <footer class="page-footer">
        <span>Central · Feito para simplificar o dia a dia.</span>
        <span>Organização que vira solução.</span>
      </footer>
    </div>
  </div>
</template>
