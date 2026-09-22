<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
  Ticket,
  Layers3,
  ArrowUpRight,
  CheckCircle2,
  ChevronRight,
  PanelLeftClose,
  PanelLeftOpen,
} from '@lucide/vue';
import { ref, computed, nextTick } from 'vue';
const page = usePage<{ flash: { success?: string } }>();
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
      <Link href="/chamados" class="brand" @click="menuOpen = false">
        <span class="brand-icon"><Ticket :size="22" /></span>
        <span>
          central
          <span class="brand-dot">.</span>
        </span>
      </Link>
      <div class="workspace">
        <span class="workspace-symbol">C</span>
        <div>
          <strong>Espaço de trabalho</strong>
          <small>Chamados internos</small>
        </div>
        <span class="workspace-dot"></span>
      </div>
      <p class="nav-label">WORKSPACE</p>
      <nav aria-label="Navegação principal">
        <Link href="/chamados" class="nav-item active" @click="menuOpen = false">
          <Layers3 :size="18" />
          Central de chamados
          <ChevronRight :size="15" />
        </Link>
      </nav>
      <div class="sidebar-note">
        <span class="note-icon"><CheckCircle2 :size="19" /></span>
        <strong>Cada pedido, um próximo passo.</strong>
        <p>Organize as solicitações e mantenha sua equipe na mesma página.</p>
        <Link href="/chamados/create" @click="menuOpen = false">
          Abrir um chamado
          <ArrowUpRight :size="15" />
        </Link>
      </div>
      <div class="sidebar-footer">
        <span class="online-dot"></span>
        Ambiente local
        <span>v1.0</span>
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
          <strong>Central de chamados</strong>
        </div>
        <div class="topbar-label">
          <span class="online-dot"></span>
          Tudo em um só lugar
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
