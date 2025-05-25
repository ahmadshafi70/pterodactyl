import http from '@/api/http';

export default async (
    uuid: string,
    mode: 'ftp' | 'sftp',
    username: string,
    password: string,
    host: string,
    port: number,
    from: string,
    to: string,
    deleteFiles: boolean
): Promise<void> => {
    await http.post(`/api/client/extensions/serverimporter/servers/${uuid}/import`, {
        mode,
        username,
        password,
        host,
        port,
        from,
        to,
        delete_files: deleteFiles,
    });
};
