<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Head, router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import { ref, computed, onMounted } from 'vue';
import {
    Folder,
    File,
    Upload,
    FolderPlus,
    FilePlus,
    Search,
    X,
    MoreVertical,
    Download,
    Copy,
    Move,
    Trash2,
    Edit,
    Eye,
    Link as LinkIcon,
    Grid3x3,
    List,
    Archive,
    Image as ImageIcon,
    FileText,
    Music,
    Video,
    FileCode,
    ChevronRight,
    Home,
} from 'lucide-vue-next';
import { dashboard } from '@/routes';
import files from '@/routes/files';
import axios from '@/axios';
import FileUploader from '@/components/FileUploader.vue';
import { getStorageUrl } from '@/utils/storage';

const toast = useToast();

interface FileItem {
    name: string;
    path: string;
    type: 'file' | 'directory';
    size?: number;
    modified: number;
    extension?: string;
}

interface Props {
    files: FileItem[];
    currentPath: string;
}

const props = defineProps<Props>();

const viewMode = ref<'grid' | 'list'>('grid');
const searchQuery = ref('');
const selectedItems = ref<Set<string>>(new Set());
const currentPath = ref(props.currentPath);
const filesList = ref<FileItem[]>(props.files);
const loading = ref(false);

// Dialogs
const showCreateFolderDialog = ref(false);
const showRenameDialog = ref(false);
const showMoveDialog = ref(false);
const showCopyDialog = ref(false);
const showUrlDialog = ref(false);
const showPreviewDialog = ref(false);
const showUploadDialog = ref(false);

// Forms
const createFolderName = ref('');
const renamePath = ref('');
const renameNewName = ref('');
const movePaths = ref<string[]>([]);
const moveDestination = ref('');
const copyPaths = ref<string[]>([]);
const copyDestination = ref('');

// Loading states
const creatingFolder = ref(false);
const renaming = ref(false);
const moving = ref(false);
const copying = ref(false);
const deleting = ref(false);
const extracting = ref(false);
const uploading = ref(false);
const fileUploaderRef = ref<InstanceType<typeof FileUploader> | null>(null);
const urlData = ref<{ path: string; url: string } | null>(null);
const previewData = ref<{ path: string; url: string; type: string } | null>(null);

const breadcrumbs = computed(() => {
    const paths = currentPath.value ? currentPath.value.split('/').filter(Boolean) : [];
    const crumbs: Array<{ title: string; href: string; path?: string }> = [
        {
            title: 'Dashboard',
            href: dashboard().url,
        },
        {
            title: 'Files',
            href: files.index().url,
        },
    ];

    let current = '';
    paths.forEach((path) => {
        current += (current ? '/' : '') + path;
        crumbs.push({
            title: path,
            href: '#',
            path: current,
        });
    });

    return crumbs;
});

const filteredFiles = computed(() => {
    if (!searchQuery.value) {
        return filesList.value;
    }

    const query = searchQuery.value.toLowerCase();
    return filesList.value.filter(
        (file) => file.name.toLowerCase().includes(query),
    );
});

const isImageFile = (file: FileItem): boolean => {
    if (file.type !== 'file') {
        return false;
    }
    const ext = file.extension?.toLowerCase() || '';
    return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext);
};

const getFileIcon = (file: FileItem) => {
    if (file.type === 'directory') {
        return Folder;
    }

    const ext = file.extension?.toLowerCase() || '';
    if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext)) {
        return ImageIcon;
    }
    if (['mp4', 'avi', 'mov', 'wmv', 'flv'].includes(ext)) {
        return Video;
    }
    if (['mp3', 'wav', 'ogg', 'flac'].includes(ext)) {
        return Music;
    }
    if (['zip', 'rar', '7z', 'tar', 'gz'].includes(ext)) {
        return Archive;
    }
    if (['js', 'ts', 'php', 'py', 'html', 'css', 'json'].includes(ext)) {
        return FileCode;
    }
    if (['pdf', 'doc', 'docx', 'txt', 'md'].includes(ext)) {
        return FileText;
    }

    return File;
};

const getImageUrl = (file: FileItem): string | null => {
    if (!isImageFile(file)) {
        return null;
    }
    // Sử dụng utility function để tạo Storage URL
    return getStorageUrl(file.path);
};

const formatSize = (bytes?: number): string => {
    if (!bytes) {
        return '-';
    }
    if (bytes < 1024) {
        return bytes + ' B';
    }
    if (bytes < 1024 * 1024) {
        return (bytes / 1024).toFixed(2) + ' KB';
    }
    if (bytes < 1024 * 1024 * 1024) {
        return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
    }
    return (bytes / (1024 * 1024 * 1024)).toFixed(2) + ' GB';
};

const formatDate = (timestamp: number): string => {
    return new Date(timestamp * 1000).toLocaleString('vi-VN');
};

const loadFiles = async (path: string = '', updateUrl: boolean = true) => {
    loading.value = true;
    try {
        const response = await axios.get(files.list().url, {
            params: { path },
        });
        filesList.value = response.data.data;
        currentPath.value = path;
        selectedItems.value.clear();

        // Update URL với query parameter
        if (updateUrl) {
            router.get(
                files.index().url,
                { path: path || undefined },
                {
                    preserveState: true,
                    replace: true,
                },
            );
        }
    } catch (error: any) {
        toast.error(error.response?.data?.message || 'Không thể tải danh sách files');
    } finally {
        loading.value = false;
    }
};

const navigateToFolder = (path: string) => {
    loadFiles(path);
};

const navigateBreadcrumb = (crumb: any) => {
    if (crumb.path !== undefined) {
        loadFiles(crumb.path);
    } else if (crumb.title === 'Files') {
        loadFiles('');
    }
};

// Handle single click - select item
const handleItemClick = (file: FileItem) => {
    toggleSelection(file.path);
};

// Handle double click - navigate/preview
const handleItemDoubleClick = (file: FileItem) => {
    if (file.type === 'directory') {
        navigateToFolder(file.path);
    } else {
        handlePreview(file);
    }
};

const handleCreateFolder = async () => {
    if (!createFolderName.value.trim()) {
        toast.error('Vui lòng nhập tên thư mục');
        return;
    }

    creatingFolder.value = true;
    try {
        const response = await axios.post(files.createFolder().url, {
            name: createFolderName.value,
            path: currentPath.value,
        });
        toast.success(response.data.message || 'Đã tạo thư mục thành công');
        showCreateFolderDialog.value = false;
        createFolderName.value = '';
        loadFiles(currentPath.value);
    } catch (error: any) {
        toast.error(error.response?.data?.message || 'Có lỗi xảy ra');
    } finally {
        creatingFolder.value = false;
    }
};

// FilePond handlers
const handleFilesUploaded = (uploadedFiles: string[]) => {
    toast.success(`Đã upload thành công ${uploadedFiles.length} file(s).`);
    loadFiles(currentPath.value);
};

const handleAllFilesComplete = (uploadedFiles: string[]) => {
    toast.success(`Đã upload thành công ${uploadedFiles.length} file(s).`);
    showUploadDialog.value = false;
    fileUploaderRef.value?.clear();
    loadFiles(currentPath.value);
};

const handleUploadError = (error: string) => {
    toast.error(error);
};

const handleDelete = async (paths: string[]) => {
    if (!confirm(`Bạn có chắc chắn muốn xóa ${paths.length} item(s)?`)) {
        return;
    }

    deleting.value = true;
    try {
        const response = await axios.post(files.delete().url, { paths });
        toast.success(response.data.message || 'Đã xóa thành công');
        loadFiles(currentPath.value);
        selectedItems.value.clear();
    } catch (error: any) {
        toast.error(error.response?.data?.message || 'Xóa thất bại');
    } finally {
        deleting.value = false;
    }
};

const openRenameDialog = (file: FileItem) => {
    renamePath.value = file.path;
    // Chỉ lấy tên file không có extension
    if (file.type === 'file' && file.extension) {
        const nameWithoutExt = file.name.replace(new RegExp(`\\.${file.extension}$`, 'i'), '');
        renameNewName.value = nameWithoutExt;
    } else {
        renameNewName.value = file.name;
    }
    showRenameDialog.value = true;
};

const handleRename = async () => {
    if (!renameNewName.value.trim()) {
        toast.error('Vui lòng nhập tên mới');
        return;
    }

    renaming.value = true;
    try {
        const response = await axios.put(files.rename().url, {
            path: renamePath.value,
            newName: renameNewName.value,
        });
        toast.success(response.data.message || 'Đã đổi tên thành công');
        showRenameDialog.value = false;
        loadFiles(currentPath.value);
    } catch (error: any) {
        toast.error(error.response?.data?.message || 'Đổi tên thất bại');
    } finally {
        renaming.value = false;
    }
};

const handleExtract = async (file: FileItem) => {
    if (!confirm(`Giải nén file ${file.name}?`)) {
        return;
    }

    extracting.value = true;
    try {
        const response = await axios.post(files.extract().url, {
            path: file.path,
            destination: currentPath.value,
        });
        toast.success(response.data.message || 'Giải nén thành công');
        loadFiles(currentPath.value);
    } catch (error: any) {
        toast.error(error.response?.data?.message || 'Giải nén thất bại');
    } finally {
        extracting.value = false;
    }
};

const handleDownload = (file: FileItem) => {
    window.open(`${files.download().url}?path=${encodeURIComponent(file.path)}`, '_blank');
};

const handleGetUrl = async (file: FileItem) => {
    try {
        const response = await axios.get(files.url().url, {
            params: { path: file.path },
        });
        urlData.value = {
            path: file.path,
            url: response.data.url,
        };
        showUrlDialog.value = true;
    } catch (error: any) {
        toast.error(error.response?.data?.message || 'Lấy URL thất bại');
    }
};

const handlePreview = async (file: FileItem) => {
    try {
        const response = await axios.get(files.url().url, {
            params: { path: file.path },
        });
        previewData.value = {
            path: file.path,
            url: response.data.url,
            type: file.extension || '',
        };
        showPreviewDialog.value = true;
    } catch (error: any) {
        toast.error(error.response?.data?.message || 'Không thể preview');
    }
};

const handleMove = async () => {
    if (!moveDestination.value.trim()) {
        toast.error('Vui lòng chọn thư mục đích');
        return;
    }

    moving.value = true;
    try {
        const response = await axios.post(files.move().url, {
            paths: movePaths.value,
            destination: moveDestination.value,
        });
        toast.success(response.data.message || 'Đã di chuyển thành công');
        showMoveDialog.value = false;
        movePaths.value = [];
        moveDestination.value = '';
        selectedItems.value.clear();
        loadFiles(currentPath.value);
    } catch (error: any) {
        toast.error(error.response?.data?.message || 'Di chuyển thất bại');
    } finally {
        moving.value = false;
    }
};

const handleCopy = async () => {
    if (!copyDestination.value.trim()) {
        toast.error('Vui lòng chọn thư mục đích');
        return;
    }

    copying.value = true;
    try {
        const response = await axios.post(files.copy().url, {
            paths: copyPaths.value,
            destination: copyDestination.value,
        });
        toast.success(response.data.message || 'Đã copy thành công');
        showCopyDialog.value = false;
        copyPaths.value = [];
        copyDestination.value = '';
        selectedItems.value.clear();
        loadFiles(currentPath.value);
    } catch (error: any) {
        toast.error(error.response?.data?.message || 'Copy thất bại');
    } finally {
        copying.value = false;
    }
};

const toggleSelection = (path: string) => {
    if (selectedItems.value.has(path)) {
        selectedItems.value.delete(path);
    } else {
        selectedItems.value.add(path);
    }
};

const selectAll = () => {
    if (selectedItems.value.size === filteredFiles.value.length) {
        selectedItems.value.clear();
    } else {
        filteredFiles.value.forEach((file) => {
            selectedItems.value.add(file.path);
        });
    }
};

const copyUrl = () => {
    if (urlData.value) {
        navigator.clipboard.writeText(urlData.value.url);
        toast.success('Đã copy URL');
    }
};

const handleDragOver = (e: DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
};

const handleDrop = (e: DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    // FilePond sẽ xử lý drag & drop tự động
    showUploadDialog.value = true;
};

onMounted(() => {
    // Load path từ URL query nếu có
    const urlParams = new URLSearchParams(window.location.search);
    const pathFromUrl = urlParams.get('path') || '';
    if (pathFromUrl) {
        loadFiles(pathFromUrl, false);
    } else if (currentPath.value) {
        loadFiles(currentPath.value, false);
    }
});
</script>

<template>

    <Head title="Quản lý Files" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-hidden rounded-xl p-6">
            <!-- Breadcrumb -->
            <Card class="w-full border shadow-sm bg-card/50 backdrop-blur-sm py-0">
                <CardContent class="px-2 py-1">
                    <Breadcrumb>
                        <BreadcrumbList class="flex-wrap items-center gap-2">
                            <BreadcrumbItem>
                                <BreadcrumbLink as-child>
                                    <button @click="navigateBreadcrumb({ title: 'Files' })"
                                        class="flex items-center gap-1.5 px-2 py-1 rounded-md hover:bg-muted hover:text-foreground transition-all duration-200 font-medium">
                                        <Home class="h-4 w-4" />
                                        <span>Files</span>
                                    </button>
                                </BreadcrumbLink>
                            </BreadcrumbItem>
                            <template v-for="(crumb, index) in breadcrumbs.slice(2)" :key="index">
                                <BreadcrumbSeparator class="mx-1" />
                                <BreadcrumbItem>
                                    <BreadcrumbLink v-if="index < breadcrumbs.slice(2).length - 1" as-child>
                                        <button @click="navigateBreadcrumb(crumb)"
                                            class="px-2 py-1 rounded-md hover:bg-muted hover:text-foreground transition-all duration-200 font-medium truncate max-w-[200px]"
                                            :title="crumb.title">
                                            {{ crumb.title }}
                                        </button>
                                    </BreadcrumbLink>
                                    <BreadcrumbPage v-else
                                        class="px-2 py-1 rounded-md bg-muted/50 font-semibold truncate max-w-[200px]"
                                        :title="crumb.title">
                                        {{ crumb.title }}
                                    </BreadcrumbPage>
                                </BreadcrumbItem>
                            </template>
                        </BreadcrumbList>
                    </Breadcrumb>
                </CardContent>
            </Card>

            <!-- Toolbar -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                <div class="flex items-center gap-2">
                    <div class="relative flex-1 sm:max-w-xs">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="searchQuery" type="text" placeholder="Tìm kiếm..." class="pl-9" />
                    </div>

                    <div class="flex items-center gap-2">
                        <Button variant="outline" size="sm"
                            :class="{ 'bg-primary text-primary-foreground': viewMode === 'grid' }"
                            @click="viewMode = 'grid'">
                            <Grid3x3 class="h-4 w-4" />
                        </Button>
                        <Button variant="outline" size="sm"
                            :class="{ 'bg-primary text-primary-foreground': viewMode === 'list' }"
                            @click="viewMode = 'list'">
                            <List class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Actions Toolbar -->
            <div class="flex items-center gap-2 flex-wrap">
                <Button variant="outline" size="sm" @click="selectAll()"
                    :class="{ 'bg-primary text-primary-foreground': selectedItems.size === filteredFiles.length && filteredFiles.length > 0 }">
                    Chọn tất cả
                </Button>
                <Button variant="outline" size="sm" @click="showCreateFolderDialog = true">
                    <FolderPlus class="mr-2 h-4 w-4" />
                    Tạo thư mục
                </Button>
                <Button variant="outline" size="sm" @click="showUploadDialog = true">
                    <Upload class="mr-2 h-4 w-4" />
                    Upload
                </Button>
                <Button v-if="selectedItems.size > 0" variant="outline" size="sm"
                    @click="handleDelete(Array.from(selectedItems))" :disabled="deleting">
                    <Spinner v-if="deleting" class="mr-2 h-4 w-4" />
                    <Trash2 v-else class="mr-2 h-4 w-4" />
                    {{ deleting ? 'Đang xóa...' : `Xóa (${selectedItems.size})` }}
                </Button>
                <Button v-if="selectedItems.size > 0" variant="outline" size="sm" @click="
                    movePaths = Array.from(selectedItems);
                showMoveDialog = true;
                ">
                    <Move class="mr-2 h-4 w-4" />
                    Di chuyển
                </Button>
                <Button v-if="selectedItems.size > 0" variant="outline" size="sm" @click="
                    copyPaths = Array.from(selectedItems);
                showCopyDialog = true;
                ">
                    <Copy class="mr-2 h-4 w-4" />
                    Copy
                </Button>
            </div>

            <!-- File Manager -->
            <Card class="flex-1 overflow-hidden">
                <CardContent class="h-full overflow-auto p-4" @dragover="handleDragOver" @drop="handleDrop">
                    <div v-if="loading" class="flex items-center justify-center h-64">
                        <div class="text-muted-foreground">Đang tải...</div>
                    </div>

                    <!-- Grid View -->
                    <div v-else-if="viewMode === 'grid'"
                        class="grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
                        <div v-for="file in filteredFiles" :key="file.path" @click="handleItemClick(file)"
                            @dblclick="handleItemDoubleClick(file)" :class="[
                                'group relative flex flex-col items-center gap-2 rounded-lg border p-4 cursor-pointer transition-colors hover:bg-muted/50',
                                selectedItems.has(file.path) ? 'border-primary bg-primary/10 ring-2 ring-primary/20' : '',
                            ]">
                            <div class="relative">
                                <div v-if="isImageFile(file) && getImageUrl(file)"
                                    class="h-12 w-12 overflow-hidden rounded border bg-muted relative">
                                    <img :src="getImageUrl(file)!" :alt="file.name" class="h-full w-full object-cover"
                                        @error="(e: any) => {
                                            const img = e.target as HTMLImageElement;
                                            if (img) {
                                                img.style.display = 'none';
                                                const fallback = img.nextElementSibling as HTMLElement;
                                                if (fallback) {
                                                    fallback.style.display = 'flex';
                                                }
                                            }
                                        }" />
                                    <div class="hidden h-full w-full items-center justify-center absolute inset-0">
                                        <ImageIcon class="h-6 w-6 text-muted-foreground" />
                                    </div>
                                </div>
                                <component v-else-if="isImageFile(file)" :is="ImageIcon" :class="[
                                    'h-12 w-12 transition-colors text-muted-foreground',
                                ]" />
                                <component v-else :is="getFileIcon(file)" :class="[
                                    'h-12 w-12 transition-colors',
                                    file.type === 'directory' ? 'text-blue-500' : 'text-muted-foreground',
                                ]" />
                                <input v-if="file.type === 'file'" type="checkbox" hidden
                                    :checked="selectedItems.has(file.path)" @click.stop="toggleSelection(file.path)"
                                    class="absolute -top-1 -right-1 h-4 w-4 rounded border-gray-300" />
                            </div>
                            <div class="w-full text-center">
                                <div class="truncate text-sm font-medium" :title="file.name">
                                    {{ file.name }}
                                </div>
                                <div v-if="file.type === 'file'" class="text-xs text-muted-foreground">
                                    {{ formatSize(file.size) }}
                                </div>
                            </div>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child @click.stop>
                                    <Button variant="ghost" size="sm"
                                        class="absolute right-2 top-2 opacity-0 group-hover:opacity-100">
                                        <MoreVertical class="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem v-if="file.type === 'file'" @click.stop="handlePreview(file)">
                                        <Eye class="mr-2 h-4 w-4" />
                                        Xem
                                    </DropdownMenuItem>
                                    <DropdownMenuItem v-if="file.type === 'file'" @click.stop="handleDownload(file)">
                                        <Download class="mr-2 h-4 w-4" />
                                        Tải xuống
                                    </DropdownMenuItem>
                                    <DropdownMenuItem @click.stop="openRenameDialog(file)">
                                        <Edit class="mr-2 h-4 w-4" />
                                        Đổi tên
                                    </DropdownMenuItem>
                                    <DropdownMenuItem v-if="file.type === 'file' && file.extension === 'zip'"
                                        @click.stop="handleExtract(file)">
                                        <Archive class="mr-2 h-4 w-4" />
                                        Giải nén
                                    </DropdownMenuItem>
                                    <DropdownMenuItem v-if="file.type === 'file'" @click.stop="handleGetUrl(file)">
                                        <LinkIcon class="mr-2 h-4 w-4" />
                                        Lấy URL
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem @click.stop="handleDelete([file.path])" class="text-destructive">
                                        <Trash2 class="mr-2 h-4 w-4" />
                                        Xóa
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>

                    <!-- List View -->
                    <div v-else class="space-y-1">
                        <div class="flex items-center gap-2 border-b pb-2 mb-2">
                            <input type="checkbox"
                                :checked="selectedItems.size === filteredFiles.length && filteredFiles.length > 0"
                                @change="selectAll" class="h-4 w-4 rounded border-gray-300" />
                            <div class="flex-1 grid grid-cols-12 gap-4 text-sm font-medium text-muted-foreground">
                                <div class="col-span-5">Tên</div>
                                <div class="col-span-2">Kích thước</div>
                                <div class="col-span-3">Ngày sửa</div>
                                <div class="col-span-2 text-right">Thao tác</div>
                            </div>
                        </div>
                        <div v-for="file in filteredFiles" :key="file.path" @click="handleItemClick(file)"
                            @dblclick="handleItemDoubleClick(file)" :class="[
                                'flex items-center gap-2 rounded-lg border p-3 cursor-pointer transition-colors hover:bg-muted/50',
                                selectedItems.has(file.path) ? 'border-primary bg-primary/10 ring-2 ring-primary/20' : '',
                            ]">
                            <input type="checkbox" :checked="selectedItems.has(file.path)"
                                @click.stop="toggleSelection(file.path)" class="h-4 w-4 rounded border-gray-300" />
                            <div v-if="isImageFile(file) && getImageUrl(file)"
                                class="h-8 w-8 overflow-hidden rounded border bg-muted flex-shrink-0 relative">
                                <img :src="getImageUrl(file)!" :alt="file.name" class="h-full w-full object-cover"
                                    @error="(e: any) => {
                                        const img = e.target as HTMLImageElement;
                                        if (img) {
                                            img.style.display = 'none';
                                            const fallback = img.nextElementSibling as HTMLElement;
                                            if (fallback) {
                                                fallback.style.display = 'flex';
                                            }
                                        }
                                    }" />
                                <div class="hidden h-full w-full items-center justify-center absolute inset-0">
                                    <ImageIcon class="h-4 w-4 text-muted-foreground" />
                                </div>
                            </div>
                            <component v-else-if="isImageFile(file)" :is="ImageIcon" :class="[
                                'h-5 w-5 text-muted-foreground',
                            ]" />
                            <component v-else :is="getFileIcon(file)" :class="[
                                'h-5 w-5',
                                file.type === 'directory' ? 'text-blue-500' : 'text-muted-foreground',
                            ]" />
                            <div class="flex-1 grid grid-cols-12 gap-4 items-center">
                                <div class="col-span-5 font-medium">{{ file.name }}</div>
                                <div class="col-span-2 text-sm text-muted-foreground">
                                    {{ formatSize(file.size) }}
                                </div>
                                <div class="col-span-3 text-sm text-muted-foreground">
                                    {{ formatDate(file.modified) }}
                                </div>
                                <div class="col-span-2 flex items-center justify-end gap-1">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child @click.stop>
                                            <Button variant="ghost" size="sm">
                                                <MoreVertical class="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem v-if="file.type === 'file'"
                                                @click.stop="handlePreview(file)">
                                                <Eye class="mr-2 h-4 w-4" />
                                                Xem
                                            </DropdownMenuItem>
                                            <DropdownMenuItem v-if="file.type === 'file'"
                                                @click.stop="handleDownload(file)">
                                                <Download class="mr-2 h-4 w-4" />
                                                Tải xuống
                                            </DropdownMenuItem>
                                            <DropdownMenuItem @click.stop="openRenameDialog(file)">
                                                <Edit class="mr-2 h-4 w-4" />
                                                Đổi tên
                                            </DropdownMenuItem>
                                            <DropdownMenuItem v-if="file.type === 'file' && file.extension === 'zip'"
                                                @click.stop="handleExtract(file)">
                                                <Archive class="mr-2 h-4 w-4" />
                                                Giải nén
                                            </DropdownMenuItem>
                                            <DropdownMenuItem v-if="file.type === 'file'"
                                                @click.stop="handleGetUrl(file)">
                                                <LinkIcon class="mr-2 h-4 w-4" />
                                                Lấy URL
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem @click.stop="handleDelete([file.path])"
                                                class="text-destructive">
                                                <Trash2 class="mr-2 h-4 w-4" />
                                                Xóa
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="!loading && filteredFiles.length === 0"
                        class="flex flex-col items-center justify-center py-16">
                        <Folder class="h-12 w-12 text-muted-foreground mb-4" />
                        <p class="text-muted-foreground">
                            {{ searchQuery ? 'Không tìm thấy file nào' : 'Thư mục trống' }}
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Upload Dialog -->
        <Dialog v-model:open="showUploadDialog">
            <DialogContent class="sm:max-w-[600px]">
                <DialogHeader>
                    <DialogTitle>Upload Files</DialogTitle>
                    <DialogDescription>
                        Kéo thả files hoặc chọn files để upload vào thư mục hiện tại.
                    </DialogDescription>
                </DialogHeader>
                <div class="py-4">
                    <FileUploader ref="fileUploaderRef" :path="currentPath" :max-files="50" :max-file-size="'500MB'"
                        @uploaded="handleFilesUploaded" @all-complete="handleAllFilesComplete"
                        @error="handleUploadError" />
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showUploadDialog = false">
                        Đóng
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Create Folder Dialog -->
        <Dialog v-model:open="showCreateFolderDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Tạo thư mục mới</DialogTitle>
                    <DialogDescription>Nhập tên thư mục</DialogDescription>
                </DialogHeader>
                <div class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label for="folder-name">Tên thư mục</Label>
                        <Input id="folder-name" v-model="createFolderName" placeholder="Tên thư mục"
                            @keyup.enter="handleCreateFolder" />
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showCreateFolderDialog = false">Hủy</Button>
                    <Button @click="handleCreateFolder" :disabled="creatingFolder">
                        {{ creatingFolder ? 'Đang tạo...' : 'Tạo' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Rename Dialog -->
        <Dialog v-model:open="showRenameDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Đổi tên</DialogTitle>
                    <DialogDescription>Nhập tên mới</DialogDescription>
                </DialogHeader>
                <div class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label for="new-name">Tên mới</Label>
                        <Input id="new-name" v-model="renameNewName" placeholder="Tên mới"
                            @keyup.enter="handleRename" />
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showRenameDialog = false">Hủy</Button>
                    <Button @click="handleRename" :disabled="renaming">
                        {{ renaming ? 'Đang đổi tên...' : 'Đổi tên' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Move Dialog -->
        <Dialog v-model:open="showMoveDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Di chuyển</DialogTitle>
                    <DialogDescription>Nhập đường dẫn đích</DialogDescription>
                </DialogHeader>
                <div class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label for="move-destination">Đường dẫn đích</Label>
                        <Input id="move-destination" v-model="moveDestination"
                            placeholder="Để trống để di chuyển vào root" @keyup.enter="handleMove" />
                        <p class="text-xs text-muted-foreground">
                            Để trống để di chuyển vào thư mục gốc
                        </p>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showMoveDialog = false">Hủy</Button>
                    <Button @click="handleMove" :disabled="moving">
                        {{ moving ? 'Đang di chuyển...' : 'Di chuyển' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Copy Dialog -->
        <Dialog v-model:open="showCopyDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Copy</DialogTitle>
                    <DialogDescription>Nhập đường dẫn đích</DialogDescription>
                </DialogHeader>
                <div class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label for="copy-destination">Đường dẫn đích</Label>
                        <Input id="copy-destination" v-model="copyDestination" placeholder="Để trống để copy vào root"
                            @keyup.enter="handleCopy" />
                        <p class="text-xs text-muted-foreground">
                            Để trống để copy vào thư mục gốc
                        </p>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showCopyDialog = false">Hủy</Button>
                    <Button @click="handleCopy" :disabled="copying">
                        {{ copying ? 'Đang copy...' : 'Copy' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- URL Dialog -->
        <Dialog v-model:open="showUrlDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>URL File</DialogTitle>
                    <DialogDescription>URL của file</DialogDescription>
                </DialogHeader>
                <div class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label>URL</Label>
                        <div class="flex gap-2">
                            <Input :value="urlData?.url" readonly class="flex-1" />
                            <Button @click="copyUrl">
                                <Copy class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showUrlDialog = false">Đóng</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Preview Dialog -->
        <Dialog v-model:open="showPreviewDialog" class="max-w-4xl">
            <DialogContent class="max-w-4xl">
                <DialogHeader>
                    <DialogTitle>{{ previewData?.path }}</DialogTitle>
                </DialogHeader>
                <div class="space-y-4 py-4 max-h-[70vh] overflow-auto">
                    <div v-if="previewData">
                        <img v-if="['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(previewData.type.toLowerCase())"
                            :src="previewData.url" alt="Preview" class="max-w-full h-auto rounded-lg" />
                        <video v-else-if="['mp4', 'avi', 'mov', 'wmv'].includes(previewData.type.toLowerCase())"
                            :src="previewData.url" controls class="max-w-full rounded-lg" />
                        <audio v-else-if="['mp3', 'wav', 'ogg'].includes(previewData.type.toLowerCase())"
                            :src="previewData.url" controls class="w-full" />
                        <iframe v-else-if="previewData.type.toLowerCase() === 'pdf'" :src="previewData.url"
                            class="w-full h-96 rounded-lg" />
                        <div v-else class="text-center py-8 text-muted-foreground">
                            Không thể preview file này
                        </div>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showPreviewDialog = false">Đóng</Button>
                    <Button v-if="previewData" @click="handleDownload({ path: previewData.path } as FileItem)">
                        <Download class="mr-2 h-4 w-4" />
                        Tải xuống
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
