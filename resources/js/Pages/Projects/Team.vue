<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="font-semibold text-xl text-gray-800 leading-tight">Equipe da Revisão</h2>
          <p class="text-sm text-gray-500 mt-1">Projeto: {{ project.title }}</p>
        </div>
        <Link :href="route('projects.show', project.id)" class="text-gray-500 hover:text-gray-700 text-sm font-medium">
          &larr; Voltar ao Projeto
        </Link>
      </div>
    </template>

    <div class="py-12 max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

      <!-- Formulário de Adição (Visível apenas para Owner e Admin) -->
      <div v-if="canManageTeam" class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Adicionar Novo Membro</h3>
        
        <form @submit.prevent="addMember" class="flex gap-4 items-end">
          <div class="flex-grow">
            <label class="block text-sm font-medium text-gray-700 mb-1">E-mail do Pesquisador</label>
            <input 
              v-model="form.email" 
              type="email" 
              placeholder="exemplo@universidade.edu" 
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              required 
            />
          </div>
          
          <div class="w-48">
            <label class="block text-sm font-medium text-gray-700 mb-1">Permissão</label>
            <select v-model="form.role" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
              <option value="collaborator">Colaborador</option>
              <option value="admin">Administrador</option>
            </select>
          </div>

          <button 
            type="submit" 
            :disabled="form.processing"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium transition disabled:opacity-50"
          >
            Convidar
          </button>
        </form>
        <div v-if="form.errors.email" class="text-red-500 text-sm mt-2">{{ form.errors.email }}</div>
      </div>

      <!-- Convites Pendentes -->
      <div v-if="invitations.length > 0 && canManageTeam" class="bg-white rounded-lg shadow-sm border border-orange-200 overflow-hidden mb-6">
        <div class="px-6 py-3 border-b border-orange-200 bg-orange-50">
          <h3 class="font-medium text-orange-800 text-sm">Convites Pendentes (Aguardando Cadastro)</h3>
        </div>
        <ul class="divide-y divide-gray-100">
          <li v-for="invite in invitations" :key="invite.id" class="px-6 py-3 flex items-center justify-between bg-white opacity-75">
            <div class="flex items-center gap-3">
              <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
              <span class="text-sm font-medium text-gray-600">{{ invite.email }}</span>
            </div>
            <div class="flex items-center gap-4">
              <span class="text-xs font-bold text-gray-400 uppercase">{{ invite.role === 'admin' ? 'Administrador' : 'Colaborador' }}</span>
              <button @click="cancelInvitation(invite.id)" class="text-red-500 hover:text-red-700 text-xs font-medium">Cancelar</button>
            </div>
          </li>
        </ul>
      </div>

      <!-- Lista de Membros -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
          <h3 class="font-medium text-gray-900">Membros Atuais ({{ team.length }})</h3>
        </div>
        
        <ul class="divide-y divide-gray-200">
          <li v-for="member in team" :key="member.id" class="px-6 py-4 flex items-center justify-between">
            
            <div class="flex flex-col">
              <span class="font-medium text-gray-900 flex items-center gap-2">
                {{ member.name }}
                <span v-if="member.id === $page.props.auth.user.id" class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">Você</span>
              </span>
              <span class="text-sm text-gray-500">{{ member.email }}</span>
            </div>

            <div class="flex items-center gap-4">
              <!-- Label de Proprietário (Ninguém mexe) -->
              <span v-if="member.pivot.role === 'owner'" class="px-3 py-1 bg-yellow-100 text-yellow-800 text-xs font-bold rounded-full uppercase">
                Proprietário
              </span>

              <!-- Menu de Permissões (Visível apenas se pode gerenciar E se não for o Owner alvo) -->
              <template v-else>
                <select 
                  v-if="canManageTeam" 
                  :value="member.pivot.role" 
                  @change="updateRole(member.id, $event.target.value)"
                  class="text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-1.5"
                >
                  <option value="admin">Admin</option>
                  <option value="collaborator">Colaborador</option>
                </select>
                
                <span v-else class="px-3 py-1 bg-gray-100 text-gray-600 text-xs font-bold rounded-full uppercase">
                  {{ member.pivot.role === 'admin' ? 'Administrador' : 'Colaborador' }}
                </span>

                <button 
                  v-if="canManageTeam" 
                  @click="removeMember(member.id, member.name)" 
                  class="text-red-500 hover:text-red-700 text-sm font-medium bg-red-50 hover:bg-red-100 px-2 py-1.5 rounded transition"
                >
                  Remover
                </button>
              </template>
            </div>

          </li>
        </ul>
      </div>

    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  project: Object,
  team: Array,
  invitations: Array,
  currentUserRole: String,
});

// Apenas Owners e Admins podem ver os botões de edição
const canManageTeam = computed(() => ['owner', 'admin'].includes(props.currentUserRole));

const form = useForm({
  email: '',
  role: 'collaborator'
});

const addMember = () => {
  form.post(route('projects.team.store', props.project.id), {
    preserveScroll: true,
    onSuccess: () => form.reset('email')
  });
};

const updateRole = (userId, newRole) => {
  router.put(route('projects.team.update', { project: props.project.id, user: userId }), {
    role: newRole
  }, { preserveScroll: true });
};

const removeMember = (userId, userName) => {
  if (confirm(`Remover ${userName} do projeto? Ele perderá acesso a esta revisão imediatamente.`)) {
    router.delete(route('projects.team.destroy', { project: props.project.id, user: userId }), {
      preserveScroll: true
    });
  }
};

const cancelInvitation = (invitationId) => {
  if (confirm('Cancelar este convite pendente? O usuário não poderá mais acessar o projeto após criar a conta.')) {
    router.delete(route('projects.invitations.destroy', { project: props.project.id, invitation: invitationId }), {
      preserveScroll: true
    });
  }
};
</script>