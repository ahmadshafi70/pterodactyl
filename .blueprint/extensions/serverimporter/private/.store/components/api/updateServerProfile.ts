import http from '@/api/http';

export default async (
    uuid: string,
    id: number,
    name: string,
    mode: 'ftp' | 'sftp',
    host: string,
    port: number
): Promise<void> => {
    await http.patch(`/api/client/extensions/serverimporter/servers/${uuid}/profiles/servers/${id}`, {
        name,
        mode,
        host,
        port,
    });
};
