import http from '@/api/http';

export default async (uuid: string, subdomainId: number): Promise<void> => {
    await http.delete(`/api/client/extensions/subdomainmanager/servers/${uuid}/${subdomainId}`);
};
