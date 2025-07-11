<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YoPrint Assessment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.prod.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>
<body class="bg-gray-50">

<div id="app" class="container mx-auto p-8 max-w-6xl">
    <div class="bg-white shadow-lg rounded-lg p-8">
        <div v-if="message" class="mb-6 p-4 rounded" :class="messageType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
            @{{ message }}
        </div>

        <form @submit.prevent="submitForm" enctype="multipart/form-data" class="mb-8">
            @csrf
            <div class="flex items-center gap-4 border-2 border-gray-300 rounded-lg p-6 hover:border-gray-400 transition-colors">
                <div 
                    class="flex-1 border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer hover:border-gray-400 transition-colors"
                    @click="triggerFileInput"
                    @dragover.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false"
                    @drop.prevent="handleDrop"
                    :class="{ 'border-blue-500 bg-blue-50': isDragging }"
                >
                    <svg v-if="!selectedFile" class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    <p class="text-gray-600 text-lg">
                        <span v-if="!selectedFile">Select file/Drag and drop</span>
                        <span v-else class="text-blue-600 font-medium">@{{ selectedFile.name }}</span>
                    </p>
                    <input 
                        type="file" 
                        ref="fileInput"
                        @change="handleFileSelect"
                        accept=".csv,.txt"
                        class="hidden"
                    >
                </div>
                <button 
                    type="submit" 
                    class="px-8 py-3 bg-gray-800 text-white font-medium rounded-lg hover:bg-gray-700 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed"
                    :disabled="!selectedFile || isUploading"
                >
                    @{{ isUploading ? 'Uploading...' : 'Upload File' }}
                </button>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="py-4 px-6 text-left text-sm font-semibold text-gray-700">
                            <button @click="sortBy('created_at')" class="flex items-center gap-1 hover:text-gray-900 transition-colors">
                                Time
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path v-if="sortField !== 'created_at'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5m0 0l5 5m-5-5v12"></path>
                                    <path v-else-if="sortOrder === 'asc'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5m0 0l5-5m-5 5V7"></path>
                                    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5m0 0l5 5m-5-5v12"></path>
                                </svg>
                            </button>
                        </th>
                        <th class="py-4 px-6 text-left text-sm font-semibold text-gray-700">
                            <button @click="sortBy('original_name')" class="flex items-center gap-1 hover:text-gray-900 transition-colors">
                                File Name
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path v-if="sortField !== 'original_name'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5m0 0l5 5m-5-5v12"></path>
                                    <path v-else-if="sortOrder === 'asc'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5m0 0l5-5m-5 5V7"></path>
                                    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5m0 0l5 5m-5-5v12"></path>
                                </svg>
                            </button>
                        </th>
                        <th class="py-4 px-6 text-left text-sm font-semibold text-gray-700">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="upload in sortedUploads" :key="upload.id" class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-4 px-6">
                            <div class="text-sm text-gray-900">
                                @{{ formatTime(upload.created_at) }}
                                <span class="text-xs text-gray-500">(@{{ getTimeAgo(upload.created_at) }})</span>
                            </div>
                        </td>
                        <td class="py-4 px-6 text-sm text-gray-900">@{{ upload.original_name }}</td>
                        <td class="py-4 px-6">
                            <span class="text-sm" :class="statusTextClass(upload.status)">
                                @{{ upload.status }}
                            </span>
                            <p v-if="upload.status === 'failed' && upload.error_message" class="text-red-500 text-xs mt-1">@{{ upload.error_message }}</p>
                        </td>
                    </tr>
                    <tr v-if="uploads.length === 0">
                        <td colspan="3" class="py-8 px-6 text-center text-gray-500">No files uploaded yet</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const { createApp, ref, computed, onMounted, onUnmounted } = Vue

    createApp({
        setup() {
            const uploads = ref([]);
            const message = ref('');
            const messageType = ref('');
            const selectedFile = ref(null);
            const isDragging = ref(false);
            const isUploading = ref(false);
            const fileInput = ref(null);
            const sortField = ref('created_at');
            const sortOrder = ref('desc');
            let intervalId = null;

            const fetchUploads = async () => {
                try {
                    const response = await axios.get('{{ route('uploads.status') }}');
                    uploads.value = response.data.data;
                } catch (error) {
                    console.error('Error fetching uploads:', error);
                }
            };

            const formatTime = (dateString) => {
                const utcDate = new Date(dateString + 'Z');
                const gmt8Date = new Date(utcDate.getTime() + (8 * 60 * 60 * 1000));
                
                const year = gmt8Date.getUTCFullYear();
                const month = (gmt8Date.getUTCMonth() + 1).toString().padStart(2, '0');
                const day = gmt8Date.getUTCDate().toString().padStart(2, '0');

                const hours = gmt8Date.getUTCHours();
                const minutes = gmt8Date.getUTCMinutes().toString().padStart(2, '0');
                const ampm = hours >= 12 ? 'PM' : 'AM';
                const displayHours = hours % 12 || 12;
                
                return `${year}-${month}-${day} ${displayHours}:${minutes} ${ampm}`;
            };

            const getTimeAgo = (dateString) => {
                const utcDate = new Date(dateString + 'Z');
                const nowUTC = new Date();
                const diffInMinutes = Math.floor((nowUTC - utcDate) / 60000);
                
                if (diffInMinutes < 1) return 'just now';
                if (diffInMinutes === 1) return '1 minute ago';
                if (diffInMinutes < 60) return `${diffInMinutes} minutes ago`;
                
                const diffInHours = Math.floor(diffInMinutes / 60);
                if (diffInHours === 1) return '1 hour ago';
                if (diffInHours < 24) return `${diffInHours} hours ago`;
                
                const diffInDays = Math.floor(diffInHours / 24);
                if (diffInDays === 1) return '1 day ago';
                return `${diffInDays} days ago`;
            };

            const sortBy = (field) => {
                if (sortField.value === field) {
                    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
                } else {
                    sortField.value = field;
                    sortOrder.value = 'desc';
                }
            };

            const sortedUploads = computed(() => {
                return [...uploads.value].sort((a, b) => {
                    let aValue, bValue;
                    
                    if (sortField.value === 'created_at') {
                        aValue = new Date(a.created_at).getTime();
                        bValue = new Date(b.created_at).getTime();
                    } else if (sortField.value === 'original_name') {
                        aValue = a.original_name.toLowerCase();
                        bValue = b.original_name.toLowerCase();
                    }
                    
                    if (sortOrder.value === 'asc') {
                        return aValue > bValue ? 1 : -1;
                    } else {
                        return aValue < bValue ? 1 : -1;
                    }
                });
            });

            const statusTextClass = (status) => {
                switch (status) {
                    case 'completed':
                        return 'text-green-600';
                    case 'processing':
                        return 'text-yellow-600';
                    case 'failed':
                        return 'text-red-600';
                    default:
                        return 'text-gray-600';
                }
            };

            const triggerFileInput = () => {
                fileInput.value.click();
            };

            const handleFileSelect = (event) => {
                const file = event.target.files[0];
                if (file) {
                    selectedFile.value = file;
                }
            };

            const handleDrop = (event) => {
                isDragging.value = false;
                const files = event.dataTransfer.files;
                if (files.length > 0) {
                    const file = files[0];
                    if (file.type === 'text/csv' || file.type === 'text/plain' || file.name.endsWith('.csv') || file.name.endsWith('.txt')) {
                        selectedFile.value = file;
                    } else {
                        message.value = 'Please upload a CSV or TXT file';
                        messageType.value = 'error';
                        setTimeout(() => {
                            message.value = '';
                        }, 3000);
                    }
                }
            };

            const submitForm = async () => {
                if (!selectedFile.value) return;

                isUploading.value = true;
                const formData = new FormData();
                formData.append('csv_file', selectedFile.value);
                formData.append('_token', '{{ csrf_token() }}');

                try {
                    const response = await axios.post('{{ route('uploads.store') }}', formData, {
                        headers: {
                            'Content-Type': 'multipart/form-data',
                        },
                    });

                    message.value = 'File uploaded successfully and is now processing.';
                    messageType.value = 'success';
                    selectedFile.value = null;
                    fileInput.value.value = '';
                    fetchUploads();
                } catch (error) {
                    if (error.response && error.response.data && error.response.data.errors) {
                        message.value = Object.values(error.response.data.errors).flat().join(', ');
                    } else {
                        message.value = 'An error occurred while uploading the file.';
                    }
                    messageType.value = 'error';
                } finally {
                    isUploading.value = false;
                    setTimeout(() => {
                        message.value = '';
                    }, 5000);
                }
            };

            onMounted(() => {
                fetchUploads();
                intervalId = setInterval(fetchUploads, 5000);

                @if(session('success'))
                    message.value = '{{ session('success') }}';
                    messageType.value = 'success';
                @endif
                @if($errors->any())
                    message.value = '{{ $errors->first() }}';
                    messageType.value = 'error';
                @endif
            });

            onUnmounted(() => {
                if (intervalId) {
                    clearInterval(intervalId);
                }
            });

            return {
                uploads,
                sortedUploads,
                message,
                messageType,
                selectedFile,
                isDragging,
                isUploading,
                fileInput,
                sortField,
                sortOrder,
                formatTime,
                getTimeAgo,
                statusTextClass,
                triggerFileInput,
                handleFileSelect,
                handleDrop,
                submitForm,
                sortBy
            }
        }
    }).mount('#app')
</script>

</body>
</html>
