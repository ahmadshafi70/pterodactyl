import http from '@/api/http';
import { DatabaseBackupResponse } from '@/components/server/databases/backup/DatabaseBackupContainer';

export default async (uuid: string): Promise<DatabaseBackupResponse> => {
    const { data } = await http.get(`/api/client/servers/${uuid}/databases/backup`);

    return data.data || [];
};
