import http from '@/api/http';
import { Allocation } from '@/api/server/getServer';

export type Subdomain = {
    object: 'server_subdomain';
    attributes: {
        id: number;
        subdomain: string;
        domain: string;
        allocation: Allocation | null;
        created_at: string;
    };
};

export default async (
    uuid: string
): Promise<{
    limit: number;
    domains: {
        id: number;
        domain: string;
    }[];
    subdomains: Subdomain[];
}> => {
    const { data } = await http.get(`/api/client/extensions/subdomainmanager/servers/${uuid}`);

    return data;
};
