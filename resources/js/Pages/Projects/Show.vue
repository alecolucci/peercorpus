<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <div>
          <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ project.title }}</h2>
          <p class="text-sm text-gray-500 mt-1">Status: {{ project.status }}</p>
        </div>
        
        <div class="flex items-center gap-3">
          <Link :href="route('projects.team.index', project.id)" class="text-gray-700 hover:bg-gray-200 bg-gray-100 px-4 py-2 rounded-md text-sm font-medium transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            Equipe
          </Link>
          <button v-if="project.owner_id === $page.props.auth.user.id" @click="deleteProject" class="text-red-600 hover:bg-red-50 px-4 py-2 rounded-md text-sm font-medium transition">
            Excluir Projeto
          </button>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          
          <!-- Coluna Principal -->
          <div class="lg:col-span-2 flex flex-col gap-6">
            
            <!-- Visão Geral -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
              <h3 class="text-lg font-medium text-gray-900 mb-2">Visão Geral</h3>
              <p class="text-gray-600 mb-6">{{ project.description || 'Nenhuma descrição fornecida.' }}</p>
              
              <div class="grid grid-cols-3 gap-4 border-t border-gray-100 pt-6">
                <div class="text-center">
                  <span class="block text-2xl font-bold text-blue-600">{{ project.articles_count || 0 }}</span>
                  <span class="text-xs text-gray-500 uppercase tracking-wide">Importados</span>
                </div>
                <div class="text-center border-l border-gray-100">
                  <span class="block text-2xl font-bold text-indigo-600">{{ triagedCount }}</span>
                  <span class="text-xs text-gray-500 uppercase tracking-wide">Triados</span>
                </div>
                <div class="text-center border-l border-gray-100">
                  <span class="block text-2xl font-bold text-emerald-600">{{ project.coded_count || 0 }}</span>
                  <span class="text-xs text-gray-500 uppercase tracking-wide">Codificados</span>
                </div>
              </div>
            </div>

            <!-- ETAPA 1: Widget de Triagem (Screening) -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-medium text-gray-900">Módulo de Triagem (Screening)</h3>
                  <p class="text-sm text-gray-500 mt-1">Leitura de Títulos e Resumos para aprovação.</p>
                </div>
                <span class="bg-indigo-100 text-indigo-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">Etapa 1</span>
              </div>

              <div class="my-6 bg-gray-50 p-4 rounded-md border border-gray-100">
                <div class="flex justify-between text-sm mb-2">
                  <span class="font-medium text-gray-700">Progresso da Equipe</span>
                  <span class="text-gray-500">{{ triageProgress }}% concluído</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5 mb-3">
                  <div class="bg-indigo-600 h-2.5 rounded-full transition-all" :style="{ width: triageProgress + '%' }"></div>
                </div>
                
                <div class="flex justify-between text-xs text-gray-500 mt-2">
                  <span>Restam: <strong>{{ project.pending_count || 0 }}</strong> pendentes</span>
                  <span class="text-green-600"><strong>{{ project.included_count || 0 }}</strong> aceitos</span>
                  <span class="text-red-600"><strong>{{ project.excluded_count || 0 }}</strong> rejeitados</span>
                </div>
              </div>

              <div class="flex justify-end gap-3">
                <Link 
                  :href="route('projects.screening', project.id)" 
                  class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700 transition focus:ring-2 focus:ring-indigo-500"
                  :class="{'opacity-50 pointer-events-none': !project.articles_count}"
                >
                  {{ project.pending_count > 0 ? 'Continuar Triagem ➔' : 'Acessar Revisões' }}
                </Link>
              </div>
            </div>

            <!-- ETAPA 2: Widget de Codificação Qualitativa -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-medium text-gray-900">Módulo de Codificação (Codebook)</h3>
                  <p class="text-sm text-gray-500 mt-1">Criação de códigos temáticos e análise qualitativa dos artigos aprovados.</p>
                </div>
                <span class="bg-emerald-100 text-emerald-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">Etapa 2</span>
              </div>

              <div class="my-6 bg-gray-50 p-4 rounded-md border border-gray-100">
                <div class="flex justify-between text-sm mb-2">
                  <span class="font-medium text-gray-700">Progresso da Análise</span>
                  <span class="text-gray-500">{{ codedProgress }}% concluído</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5 mb-3">
                  <div class="bg-emerald-500 h-2.5 rounded-full transition-all" :style="{ width: codedProgress + '%' }"></div>
                </div>
                
                <div class="flex justify-between text-xs text-gray-500 mt-2">
                  <span>Aprovados na Triagem: <strong>{{ project.included_count || 0 }}</strong> artigos</span>
                  <span class="text-emerald-600"><strong>{{ project.coded_count || 0 }}</strong> analisados</span>
                </div>
              </div>

              <div class="flex justify-end gap-3">
                <Link 
                  :href="route('projects.coding', project.id)" 
                  class="px-4 py-2 bg-emerald-600 text-white rounded-md text-sm font-medium hover:bg-emerald-700 transition focus:ring-2 focus:ring-emerald-500"
                  :class="{'opacity-50 pointer-events-none': !project.included_count}"
                >
                  Acessar Codebook ➔
                </Link>
              </div>
            </div>

          </div>

          <!-- Coluna Lateral: Importador -->
          <div class="lg:col-span-1">
            <ArticleImporter :project-id="project.id" />
          </div>

        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { router, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import ArticleImporter from '@/Components/ArticleImporter.vue';

const props = defineProps({
  project: Object,
});

// Cálculos da Triagem (Screening - Etapa 1)
const triagedCount = computed(() => {
  return (props.project.included_count || 0) + (props.project.excluded_count || 0);
});

const triageProgress = computed(() => {
  const total = props.project.articles_count || 0;
  if (total === 0) return 0;
  return Math.round((triagedCount.value / total) * 100);
});

// Cálculos da Codificação (Codebook - Etapa 2)
const codedProgress = computed(() => {
  const totalIncluded = props.project.included_count || 0;
  if (totalIncluded === 0) return 0; // Evita divisão por zero
  return Math.round(((props.project.coded_count || 0) / totalIncluded) * 100);
});

const deleteProject = () => {
  if (confirm('Tem certeza que deseja excluir esta revisão e TODOS os artigos importados? Esta ação é irreversível.')) {
    router.delete(route('projects.destroy', props.project.id));
  }
};
</script>
