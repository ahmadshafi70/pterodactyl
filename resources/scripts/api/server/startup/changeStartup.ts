import http from '@/api/http';

export default (uuid: string, startup: string): Promise<any> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/startup/startup/change`, {
            startup,
        })
            .then((data) => {
                resolve(data.data || []);
            })
            .catch(reject);
    });
};
