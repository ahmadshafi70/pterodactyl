import http from '@/api/http';

export default (uuid: string, name: string, database: number): Promise<any> => {
    return new Promise((resolve, reject) => {
        http.post(
            `/api/client/servers/${uuid}/databases/backup/create`,
            {
                name,
                database,
            },
            {
                timeout: 120000,
            }
        )
            .then((data) => {
                resolve(data.data || []);
            })
            .catch(reject);
    });
};
