import React, { useEffect } from 'react';
import CreateBackupButton from '@/components/server/databases/backup/CreateBackupButton';
import useSWR from 'swr';
import { ServerContext } from '@/state/server';
import getDatabaseBackups from '@/api/server/databases/backup/getDatabaseBackups';
import useFlash from '@/plugins/useFlash';
import Spinner from '@/components/elements/Spinner';
import tw from 'twin.macro';
import DatabaseBackupRow from '@/components/server/databases/backup/DatabaseBackupRow';

export interface DatabaseBackupResponse {
    limit: number;
    backups: any[];
    databases: any[];
}

export default () => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);

    const { clearAndAddHttpError, clearFlashes } = useFlash();

    const { data, error, mutate } = useSWR<DatabaseBackupResponse>([uuid, '/databases/backups'], (uuid) =>
        getDatabaseBackups(uuid)
    );

    useEffect(() => {
        if (!error) {
            clearFlashes('databases');
        } else {
            clearAndAddHttpError({ key: 'databases', error });
        }
    }, [error]);

    return (
        <>
            <div css={tw`w-full mt-6`} />
            {!data ? (
                <Spinner size={'large'} centered />
            ) : (
                <>
                    {data.backups.length > 0 ? (
                        data.backups.map((item, key) => (
                            <DatabaseBackupRow backup={item} key={key} onUpdate={() => mutate()} />
                        ))
                    ) : (
                        <p css={tw`text-center text-sm text-neutral-300`}>
                            {data.limit > 0
                                ? 'It looks like you have no database backups.'
                                : 'Database backups cannot be created for this server.'}
                        </p>
                    )}
                    <div css={tw`mt-6 flex items-center justify-end`}>
                        {data.limit > 0 && (
                            <p css={tw`text-sm text-neutral-300 mb-4 sm:mr-6 sm:mb-0`}>
                                {data.backups.length} of {data.limit} backups have been created to this server.
                            </p>
                        )}
                        {data.limit > 0 && data.limit > data.backups.length && data.databases.length > 0 && (
                            <CreateBackupButton
                                databases={data.databases}
                                css={tw`flex justify-end mt-6`}
                                onCreated={() => mutate()}
                            />
                        )}
                    </div>
                </>
            )}
        </>
    );
};
