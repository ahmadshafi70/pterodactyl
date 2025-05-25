import http from '@/api/http';
import { Subdomain } from './getData';

export default async (uuid: string, subdomain: string, domain: number, allocation: number): Promise<Subdomain> => {
    const { data } = await http.post(`/api/client/extensions/subdomainmanager/servers/${uuid}`, {
        subdomain,
        domain,
        allocation,
    });

    return data;
};
