import http from '@/api/http';
import { CredentialProfile } from './getProfiles';

export default async (
    uuid: string,
    name: string,
    username: string | null,
    password: string
): Promise<CredentialProfile> => {
    const data = await http.post(`/api/client/extensions/serverimporter/servers/${uuid}/profiles/credentials`, {
        name,
        username,
        password,
    });

    return data.data;
};
