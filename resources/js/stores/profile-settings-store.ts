import { create } from 'zustand';
import { devtools } from 'zustand/middleware';

interface ProfileSettingsState {
    // Active tab
    activeTab: 'personal' | 'bank' | 'avatar' | 'passwords';
    setActiveTab: (tab: 'personal' | 'bank' | 'avatar' | 'passwords') => void;

    // Avatar preview
    avatarPreview: string | null;
    setAvatarPreview: (preview: string | null) => void;

    // Signature data
    signatureData: string | null;
    setSignatureData: (data: string | null) => void;

    // Form states
    isEditingPersonal: boolean;
    setIsEditingPersonal: (isEditing: boolean) => void;

    isEditingBank: boolean;
    setIsEditingBank: (isEditing: boolean) => void;

    // Upload progress
    uploadProgress: number;
    setUploadProgress: (progress: number) => void;

    // Reset all state
    reset: () => void;
}

const initialState = {
    activeTab: 'personal' as const,
    avatarPreview: null,
    signatureData: null,
    isEditingPersonal: false,
    isEditingBank: false,
    uploadProgress: 0,
};

export const useProfileSettingsStore = create<ProfileSettingsState>()(
    devtools(
        (set) => ({
            ...initialState,

            // Tab management
            setActiveTab: (tab) => set({ activeTab: tab }),

            // Avatar preview
            setAvatarPreview: (preview) => set({ avatarPreview: preview }),

            // Signature data
            setSignatureData: (data) => set({ signatureData: data }),

            // Form editing states
            setIsEditingPersonal: (isEditing) => set({ isEditingPersonal: isEditing }),
            setIsEditingBank: (isEditing) => set({ isEditingBank: isEditing }),

            // Upload progress
            setUploadProgress: (progress) => set({ uploadProgress: progress }),

            // Reset
            reset: () => set(initialState),
        }),
        { name: 'ProfileSettingsStore' }
    )
);
