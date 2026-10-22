<script setup>
import { ref, onUnmounted, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage, router, Link } from '@inertiajs/vue3';

const page = usePage();

const props = defineProps({
    projects: Array,
});

const formProject = useForm({
    title: '',
    description: '',
});

const submitProject = () => {
    formProject.post(route('projects.store'), {
        onSuccess: () => formProject.reset(),
    });
};

const formUpload = useForm({
    bibliography_file: null,
});

const activeProjectForUpload = ref(null);
const toastMessage = ref(''); // Estado para a nossa notificação flutuante

// Função para mostrar o Toast por 5 segundos
const showToast = (message) => {
    toastMessage.value = message;
    setTimeout(() => {
        toastMessage.value = '';
    }, 5000);
};

const triggerUpload = (projectId) => {
    activeProjectForUpload.value = projectId;
    document.getElementById(`file-upload-${projectId}`).click();
};

const handleFileChange = (event, projectId) => {
    const file = event.target.files[0];
    if (!file) return;

    formUpload.bibliography_file = file;
    formUpload.post(route('projects.import', projectId), {
        onSuccess: () => {
            showToast('Upload iniciado! O arquivo está sendo processado em background.');
            formUpload.reset();
        },
    });
};

// Monitora mensagens flash do Laravel (sucesso ou erro)
watch(() => page.props.flash, (flash) => {
    if (flash.success) {
        showToast(flash.success);
        page.props.flash.success = null;
    }
    if (flash.error) {
        showToast(`⚠️ Aviso: ${flash.error}`);
        page.props.flash.error = null;
    }
}, { immediate: true, deep: true });

// Criamos um "Set" para lembrar quais projetos já estamos escutando,
// evitando escutar o mesmo projeto duas vezes.
const subscribedChannels = new Set();

// Em vez de onMounted, vigiamos a lista de projetos reagindo a novos cadastros
watch(() => props.projects, (newProjects) => {
    newProjects.forEach(project => {
        // Se ainda não estamos escutando este projeto, inscrevemos agora!
        if (!subscribedChannels.has(project.id)) {
            window.Echo.private(`project.${project.id}`)
                .listen('ImportCompleted', (event) => {
                    showToast(event.message); 
                    router.reload({ only: ['projects'], preserveState: true, preserveScroll: true });
                });
            
            // Marca como inscrito
            subscribedChannels.add(project.id);
        }
    });
}, { immediate: true }); // O "immediate: true" faz ele rodar logo ao carregar a página também

// Lógica de Convite de Membros
const newMemberEmails = ref({}); 
const activeInviteProjectId = ref(null); 
const formMember = useForm({ email: '' });

const addMember = (projectId) => {
    activeInviteProjectId.value = projectId;
    formMember.email = newMemberEmails.value[projectId] || '';

    formMember.post(route('project.members.store', projectId), {
        preserveScroll: true,
        onSuccess: () => {
            newMemberEmails.value[projectId] = ''; // Limpa o campo do projeto correto
            formMember.reset();
            activeInviteProjectId.value = null;
        },
        onError: () => {
            // Em caso de erro de validação (ex: e-mail não existe), mantém o estado para mostrar o erro na tela
        }
    });
};

// Quando o usuário sai da tela, limpamos todos os canais
onUnmounted(() => {
    subscribedChannels.forEach(id => {
        window.Echo.leave(`project.${id}`);
    });
});
</script>

<template>
    <Head title="Meus Projetos" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Projetos de Revisão</h2>
        </template>

        <div class="py-12 relative">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Formulário para Novo Projeto -->
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Criar Novo Projeto</h3>
                    <form @submit.prevent="submitProject" class="flex gap-4 items-start">
                        <div class="flex-1">
                            <input v-model="formProject.title" type="text" placeholder="Título da Revisão Sistemática" 
                                class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                        </div>
                        <div class="flex-1">
                            <input v-model="formProject.description" type="text" placeholder="Breve descrição (Opcional)" 
                                class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        </div>
                        <button type="submit" :disabled="formProject.processing" 
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50 transition">
                            Criar
                        </button>
                    </form>
                </div>

                <!-- Lista de Projetos -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="project in projects" :key="project.id" class="bg-white p-6 shadow sm:rounded-lg flex flex-col justify-between hover:shadow-md transition">
                        <div>
                            <div class="flex justify-between items-start">
                                <h4 class="text-xl font-bold text-gray-800">{{ project.title }}</h4>
                                <!-- Contador Visual de Artigos -->
                                <span class="bg-indigo-100 text-indigo-800 text-xs font-bold px-2.5 py-1 rounded-full">
                                    {{ project.articles_count || 0 }} Artigos
                                </span>
                            </div>
                            <p class="text-gray-600 text-sm mt-2 line-clamp-3">{{ project.description || 'Sem descrição.' }}</p>                            
                            <div class="mt-4">
                                <span class="inline-block px-2 py-1 bg-blue-50 text-blue-700 text-xs font-semibold rounded uppercase tracking-wider border border-blue-200">
                                    Fase: {{ project.status }}
                                </span>
                            </div>
                        </div>

                        <!-- Convite de Membros -->
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <h5 class="text-xs font-bold text-gray-500 uppercase mb-2">Equipe</h5>
                            <form @submit.prevent="addMember(project.id)" class="flex flex-col gap-1">
                                <div class="flex gap-2">
                                    <input v-model="newMemberEmails[project.id]" type="email" placeholder="E-mail do colega..." required
                                        class="flex-1 text-sm border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500">
                                    
                                    <button type="submit" :disabled="formMember.processing && activeInviteProjectId === project.id" 
                                        class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded text-sm font-bold hover:bg-indigo-200 transition disabled:opacity-50">
                                        <span v-if="formMember.processing && activeInviteProjectId === project.id">...</span>
                                        <span v-else>Convidar</span>
                                    </button>
                                </div>
                                
                                <!-- Mensagem de Erro de Validação -->
                                <span v-if="activeInviteProjectId === project.id && formMember.errors.email" class="text-xs text-red-600 font-semibold mt-1">
                                    {{ formMember.errors.email }}
                                </span>
                            </form>
                        </div>
                        
                        <!-- Ações do Projeto -->
                        <div class="mt-6 pt-4 border-t border-gray-100 flex justify-between items-center">
                            
                            <Link :href="route('screening.index', project.id)" class="text-indigo-600 hover:text-indigo-800 font-semibold text-sm">
                                Acessar Triagem &rarr;
                            </Link>

                            <Link :href="route('coding.index', project.id)" class="text-green-600 hover:text-green-800 font-semibold text-sm">
                                Workspace &rarr;
                            </Link>
                            
                            <input :id="`file-upload-${project.id}`" type="file" class="hidden" accept=".ris,.bib" 
                                @change="(e) => handleFileChange(e, project.id)">
                            
                            <button @click="triggerUpload(project.id)" :disabled="formUpload.processing && activeProjectForUpload === project.id"
                                class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium text-sm py-1.5 px-3 rounded flex items-center gap-2 border border-gray-300 transition">
                                <span v-if="formUpload.processing && activeProjectForUpload === project.id">Enviando...</span>
                                <span v-else>+ Importar</span>
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Toast Notification (Flutuante no canto inferior direito) -->
            <transition leave-active-class="transition ease-in duration-300" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-4">
                <div v-if="toastMessage" class="fixed bottom-6 right-6 bg-gray-900 text-white px-6 py-4 rounded-lg shadow-xl z-50 flex items-center gap-3">
                    <span class="font-medium">{{ toastMessage }}</span>
                </div>
            </transition>
        </div>
    </AuthenticatedLayout>
</template>