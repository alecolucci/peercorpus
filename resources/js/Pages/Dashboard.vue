<template>
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        
        <!-- Banner de Boas-Vindas e Estatísticas -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 flex flex-col md:flex-row justify-between items-center p-6">
          <div>
            <h3 class="text-lg font-bold text-gray-900">Olá, {{ $page.props.auth.user.name }}!</h3>
            <p class="text-gray-600 mt-1">Bem-vindo de volta ao seu painel de revisões sistemáticas.</p>
          </div>
          
          <div class="mt-4 md:mt-0 flex items-center gap-6">
            <div class="text-center">
              <span class="block text-2xl font-black text-indigo-600">{{ totalProjects }}</span>
              <span class="text-xs text-gray-500 uppercase tracking-wide font-medium">Projetos Ativos</span>
            </div>
            
            <Link :href="route('projects.create')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-md text-sm font-medium transition shadow-sm">
              + Nova Revisão
            </Link>
          </div>
        </div>

        <!-- Seção de Projetos Recentes -->
        <div>
          <div class="flex justify-between items-end mb-4 px-1">
            <h3 class="text-lg font-semibold text-gray-800">Projetos Recentes</h3>
            <Link v-if="totalProjects > 0" :href="route('projects.index')" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
              Ver todos &rarr;
            </Link>
          </div>

          <!-- Estado Vazio -->
          <div v-if="recentProjects.length === 0" class="bg-white p-12 text-center rounded-lg shadow-sm border border-gray-200 border-dashed">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum projeto encontrado</h3>
            <p class="mt-1 text-sm text-gray-500">Comece sua primeira revisão sistemática ou aguarde um convite.</p>
            <div class="mt-6">
              <Link :href="route('projects.create')" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                Criar Projeto
              </Link>
            </div>
          </div>

          <!-- Grid de Projetos -->
          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div v-for="project in recentProjects" :key="project.id" class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 flex flex-col hover:shadow-md transition group">
              <div class="flex justify-between items-start mb-3">
                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full uppercase tracking-wider"
                  :class="project.status === 'setup' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'">
                  {{ project.status }}
                </span>
                
                <!-- Tag para identificar se é membro ou dono -->
                <span v-if="project.owner_id !== $page.props.auth.user.id" title="Você é membro desta equipe" class="text-gray-400">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </span>
              </div>
              
              <h3 class="text-base font-bold text-gray-900 truncate mb-2 group-hover:text-indigo-600 transition-colors" :title="project.title">
                {{ project.title }}
              </h3>
              
              <div class="flex items-center text-xs text-gray-500 mb-4 mt-auto">
                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                {{ project.articles_count }} referências
              </div>

              <div class="border-t border-gray-100 pt-3 mt-1">
                <Link :href="route('projects.show', project.id)" class="block w-full text-center text-sm font-medium text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 py-1.5 rounded transition">
                  Acessar Revisão
                </Link>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
  recentProjects: Array,
  totalProjects: Number,
});
</script>
