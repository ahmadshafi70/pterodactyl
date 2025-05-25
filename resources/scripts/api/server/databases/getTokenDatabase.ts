import http from '@/api/http';

export default (uuid: string, database: string): Promise<any> => {
    return new Promise((resolve) => {
        http.post(`/api/client/servers/${uuid}/databases/${database}/getToken`)
            .then((response) => {
                resolve(response.data.data);
            })
            .catch((error) => {
                console.log(error);
            });
    });
};
