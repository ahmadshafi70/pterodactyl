import http from '@/api/http';
import { ServerProfile } from './getProfiles';

export default async (
    uuid: string,
    name: string,
    mode: 'ftp' | 'sftp',
    host: string,
    port: number
): Promise<ServerProfile> => {
    const data = await http.post(`/api/client/extensions/serverimporter/servers/${uuid}/profiles/servers`, {
        name,
        mode,
        host,
        port,
    });

    return data.data;
};
