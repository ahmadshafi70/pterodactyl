import http from '@/api/http';

export default async (uuid: string, build: number, deleteFiles: boolean, acceptEula: boolean): Promise<void> => {
    await http.post(
        `/api/client/extensions/versionchanger/servers/${uuid}/install`,
        {
            build,
            delete_files: deleteFiles,
            accept_eula: acceptEula,
        },
        {
            timeout: Infinity,
        }
    );

    return;
};
