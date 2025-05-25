import React, { useState } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import useFlash from '@/plugins/useFlash';
import { faArchive, faBoxOpen, faCloudDownloadAlt, faEllipsisH } from '@fortawesome/free-solid-svg-icons';
import { format, formatDistanceToNow } from 'date-fns';
import Spinner from '@/components/elements/Spinner';
import { bytesToString } from '@/lib/formatters';
import Can from '@/components/elements/Can';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import DropdownMenu, { DropdownButtonRow } from '@/components/elements/DropdownMenu';
import { ServerContext } from '@/state/server';
import tw from 'twin.macro';
import GreyRowBox from '@/components/elements/GreyRowBox';
import { NodeBackupServer, NodeBackupResponse } from '@/components/server/backups/BackupContainer';
import Input from '@/components/elements/Input';
import { Dialog } from '@/components/elements/dialog';
import http from '@/api/http';
import useWebsocketEvent from '@/plugins/useWebsocketEvent';
import { SocketEvent } from '@/components/server/events';

interface Props {
    backup: NodeBackupServer;
    className?: string;
    mutate: any;
}

export default ({ backup, className, mutate }: Props) => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const setServerFromState = ServerContext.useStoreActions((actions) => actions.server.setServerFromState);
    const [loading, setLoading] = useState(false);
    const [truncate, setTruncate] = useState(false);
    const [modal, setModal] = useState('');
    const { clearFlashes, clearAndAddHttpError } = useFlash();

    useWebsocketEvent(`${SocketEvent.BACKUP_COMPLETED}` as SocketEvent, (data) => {
        try {
            const parsed = JSON.parse(data);

            if (parsed.uuid !== backup.uuid) {
                return;
            }

            mutate(
                (data: NodeBackupResponse) => ({
                    ...data,
                    items: data.items.map((b) =>
                        b.uuid !== backup.uuid
                            ? b
                            : {
                                  ...b,
                                  isSuccessful: parsed.is_successful || true,
                                  checksum: (parsed.checksum_type || '') + ':' + (parsed.checksum || ''),
                                  bytes: parsed.file_size || 0,
                                  completedAt: new Date(),
                              }
                    ),
                }),
                false
            );
        } catch (e) {
            console.warn(e);
        }
    });

    const doDownload = () => {
        setLoading(true);
        clearFlashes('node-backups');
        http.get(`/api/client/servers/${uuid}/node-backups/${backup.uuid}/download`)
            .then(({ data }) => (window.location = data.attributes.url))
            .catch((error) => {
                console.error(error);
                clearAndAddHttpError({ key: 'node-backups', error });
            })
            .then(() => setLoading(false));
    };

    const doRestorationAction = () => {
        setLoading(true);
        clearFlashes('node-backups');
        http.post(`/api/client/servers/${uuid}/node-backups/${backup.uuid}/restore`, {
            truncate,
        })
            .then(() =>
                setServerFromState((s) => ({
                    ...s,
                    status: 'restoring_backup',
                }))
            )
            .catch((error) => {
                console.error(error);
                clearAndAddHttpError({ key: 'node-backups', error });
            })
            .then(() => setLoading(false))
            .then(() => setModal(''));
    };

    return (
        <GreyRowBox css={tw`flex-wrap md:flex-nowrap items-center`} className={className}>
            <div css={tw`flex items-center truncate w-full md:flex-1`}>
                <div css={tw`mr-4`}>
                    {backup.completedAt !== null ? (
                        <FontAwesomeIcon icon={faArchive} css={tw`text-neutral-300`} />
                    ) : (
                        <Spinner size={'small'} />
                    )}
                </div>
                <div css={tw`flex flex-col truncate`}>
                    <div css={tw`flex items-center text-sm mb-1`}>
                        {backup.completedAt !== null && !backup.isSuccessful && (
                            <span
                                css={tw`bg-red-500 py-px px-2 rounded-full text-white text-xs uppercase border border-red-600 mr-2`}
                            >
                                Failed
                            </span>
                        )}
                        <p css={tw`break-words truncate`}>{backup.uuid}</p>
                        {backup.completedAt !== null && backup.isSuccessful && (
                            <span css={tw`ml-3 text-neutral-300 text-xs font-extralight hidden sm:inline`}>
                                {bytesToString(backup.bytes)}
                            </span>
                        )}
                    </div>
                    <p css={tw`mt-1 md:mt-0 text-xs text-neutral-400 font-mono truncate`}>{backup.checksum}</p>
                </div>
            </div>
            <div css={tw`flex-1 md:flex-none md:w-48 mt-4 md:mt-0 md:ml-8 md:text-center`}>
                <p title={format(backup.createdAt, 'ddd, MMMM do, yyyy HH:mm:ss')} css={tw`text-sm`}>
                    {formatDistanceToNow(backup.createdAt, { includeSeconds: true, addSuffix: true })}
                </p>
                <p css={tw`text-2xs text-neutral-500 uppercase mt-1`}>Created</p>
            </div>
            <Can action={['backup.download', 'backup.restore', 'backup.delete']} matchAny>
                <div css={tw`mt-4 md:mt-0 ml-6`} style={{ marginRight: '-0.5rem' }}>
                    {!backup.completedAt ? (
                        <div css={tw`p-2 invisible`}>
                            <FontAwesomeIcon icon={faEllipsisH} />
                        </div>
                    ) : (
                        <span>
                            <Dialog.Confirm
                                open={modal === 'restore'}
                                onClose={() => setModal('')}
                                confirm={'Restore'}
                                title={`Restore "${backup.uuid}"`}
                                onConfirmed={() => doRestorationAction()}
                            >
                                <p>
                                    Your server will be stopped. You will not be able to control the power state, access
                                    the file manager, or create additional backups until completed.
                                </p>
                                <p css={tw`mt-4 -mb-2 bg-gray-700 p-3 rounded`}>
                                    <label
                                        htmlFor={'restore_truncate'}
                                        css={tw`text-base flex items-center cursor-pointer`}
                                    >
                                        <Input
                                            type={'checkbox'}
                                            css={tw`text-red-500! w-5! h-5! mr-2`}
                                            id={'restore_truncate'}
                                            value={'true'}
                                            checked={truncate}
                                            onChange={() => setTruncate((s) => !s)}
                                        />
                                        Delete all files before restoring backup.
                                    </label>
                                </p>
                            </Dialog.Confirm>
                            <SpinnerOverlay visible={loading} fixed />
                            {backup.isSuccessful && (
                                <DropdownMenu
                                    renderToggle={(onClick) => (
                                        <button
                                            onClick={onClick}
                                            css={tw`text-gray-200 transition-colors duration-150 hover:text-gray-100 p-2`}
                                        >
                                            <FontAwesomeIcon icon={faEllipsisH} />
                                        </button>
                                    )}
                                >
                                    <div css={tw`text-sm`}>
                                        <Can action={'backup.download'}>
                                            <DropdownButtonRow onClick={doDownload}>
                                                <FontAwesomeIcon
                                                    fixedWidth
                                                    icon={faCloudDownloadAlt}
                                                    css={tw`text-xs`}
                                                />
                                                <span css={tw`ml-2`}>Download</span>
                                            </DropdownButtonRow>
                                        </Can>
                                        <Can action={'backup.restore'}>
                                            <DropdownButtonRow onClick={() => setModal('restore')}>
                                                <FontAwesomeIcon fixedWidth icon={faBoxOpen} css={tw`text-xs`} />
                                                <span css={tw`ml-2`}>Restore</span>
                                            </DropdownButtonRow>
                                        </Can>
                                    </div>
                                </DropdownMenu>
                            )}
                        </span>
                    )}
                </div>
            </Can>
        </GreyRowBox>
    );
};
