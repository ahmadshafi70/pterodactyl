import http from '@/api/http';

export type MinecraftVersionProviderType = {
    name: string;
    identifier: string;
    icon: string;
    homepage: string;
    description: string;
    experimental: boolean;
    deprecated: boolean;
    builds: number;
    versions: {
        minecraft: number;
        project: number;
    };
};

export default async (uuid: string): Promise<Record<string, MinecraftVersionProviderType[]>> => {
    const { data } = await http.get<{
        types: Record<string, Record<string, MinecraftVersionProviderType>>;
    }>(`/api/client/extensions/versionchanger/servers/${uuid}/types`);

    return Object.entries(data.types).reduce<Record<string, MinecraftVersionProviderType[]>>((acc, [key, value]) => {
        acc[key] = Object.entries(value).map(([t, v]) => Object.assign(v, { identifier: t }));

        return acc;
    }, {});
};
