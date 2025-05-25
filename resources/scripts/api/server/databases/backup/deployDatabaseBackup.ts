import http from '@/api/http';

export default (uuid: string, backupId: number): Promise<any> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/databases/backup/${backupId}/deploy`, {})
            .then((data) => {
                resolve(data.data || []);
            })
            .catch(reject);
    });
};
