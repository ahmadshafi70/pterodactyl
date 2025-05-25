import http from '@/api/http';

export default async (uuid: string, subdomainId: number, allocation: number): Promise<void> => {
    await http.patch(`/api/client/extensions/subdomainmanager/servers/${uuid}/${subdomainId}`, { allocation });
};
