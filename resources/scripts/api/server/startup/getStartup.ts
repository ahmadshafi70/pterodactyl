import http from '@/api/http';
import { StartupResponse } from '@/components/server/startup/ChangeStartupBox';

export default async (uuid: string): Promise<StartupResponse> => {
    const { data } = await http.get(`/api/client/servers/${uuid}/startup/startup`);

    return data.data || [];
};
