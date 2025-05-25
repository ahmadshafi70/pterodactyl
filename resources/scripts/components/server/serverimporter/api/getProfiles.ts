import http from '@/api/http';

export type CredentialProfile = {
    object: 'credential_profile';
    attributes: {
        id: number;
        name: string;
        username: string | null;
        password: string;
    };
};

export type ServerProfile = {
    object: 'server_profile';
    attributes: {
        id: number;
        name: string;
        mode: 'ftp' | 'sftp';
        host: string;
        port: number;
    };
};

export default async (
    uuid: string
): Promise<{
    credentials: CredentialProfile[];
    servers: ServerProfile[];
}> => {
    const { data } = await http.get(`/api/client/extensions/serverimporter/servers/${uuid}/profiles`);

    return {
        credentials: data.credentials.data,
        servers: data.servers.data,
    };
};
