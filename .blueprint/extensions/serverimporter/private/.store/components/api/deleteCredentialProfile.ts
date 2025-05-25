import http from '@/api/http';

export default async (uuid: string, id: number): Promise<void> => {
    await http.delete(`/api/client/extensions/serverimporter/servers/${uuid}/profiles/credentials/${id}`);
};
