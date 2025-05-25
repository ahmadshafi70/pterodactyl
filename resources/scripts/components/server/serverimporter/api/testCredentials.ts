import http from '@/api/http';

export default async (
    uuid: string,
    mode: 'ftp' | 'sftp',
    username: string,
    password: string,
    host: string,
    port: number,
    from: string
): Promise<void> => {
    await http.post(`/api/client/extensions/serverimporter/servers/${uuid}/test-credentials`, {
        mode,
        username,
        password,
        host,
        port,
        from,
    });
};
