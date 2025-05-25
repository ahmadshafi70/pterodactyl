import http from '@/api/http';

export default (uuid: string, backupId: number): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.delete(`/api/client/servers/${uuid}/databases/backup/${backupId}/delete`)
            .then(() => resolve())
            .catch(reject);
    });
};
