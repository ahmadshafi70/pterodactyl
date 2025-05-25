import http from '@/api/http';

export default async (
    uuid: string,
    id: number,
    name: string,
    username: string | null,
    password: string
): Promise<void> => {
    await http.patch(`/api/client/extensions/serverimporter/servers/${uuid}/profiles/credentials/${id}`, {
        name,
        username,
        password,
    });
};
