import React, { useState } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faTrashAlt, faServer, faBoxOpen, faDownload } from '@fortawesome/free-solid-svg-icons';
import Modal from '@/components/elements/Modal';
import FlashMessageRender from '@/components/FlashMessageRender';
import { ServerContext } from '@/state/server';
import useFlash from '@/plugins/useFlash';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import GreyRowBox from '@/components/elements/GreyRowBox';
import deleteDatabaseBackup from '@/api/server/databases/backup/deleteDatabaseBackup';
import deployDatabaseBackup from '@/api/server/databases/backup/deployDatabaseBackup';

interface Props {
    backup: any;
    onUpdate: () => void;
}

export default ({ backup, onUpdate }: Props) => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);

    const { clearAndAddHttpError, clearFlashes } = useFlash();

    const [deployVisible, setDeployVisible] = useState(false);
    const [deployInProgress, setDeployInProgress] = useState(false);
    const [deployId, setDeployId] = useState(0);
    const [visible, setVisible] = useState(false);
    const [deleteId, setDeleteId] = useState(0);

    const onDeploy = () => {
        clearFlashes('databases');
        clearFlashes('database:backup:deploy');
        setDeployInProgress(true);

        deployDatabaseBackup(uuid, deployId)
            .then(() => {
                setDeployVisible(false);
            })
            .catch((error) => {
                clearAndAddHttpError({ key: 'database:backup:deploy', error });
            })
            .finally(() => {
                setDeployInProgress(false);
            });
    };

    const onDelete = () => {
        clearFlashes('databases');
        clearFlashes('database:backup:delete');

        deleteDatabaseBackup(uuid, deleteId)
            .then(() => {
                setVisible(false);
                onUpdate();
            })
            .catch((error) => {
                clearAndAddHttpError({ key: 'database:backup:delete', error });
            });
    };

    return (
        <>
            <Modal
                visible={deployVisible}
                onDismissed={() => setDeployVisible(false)}
                showSpinnerOverlay={deployInProgress}
            >
                <FlashMessageRender byKey={'database:backup:deploy'} css={tw`mb-6`} />
                <h2 css={tw`text-2xl mb-6`}>Confirm database restore</h2>
                <p css={tw`text-sm`}>Current content of the database will be deleted. Are you sure?</p>
                <div css={tw`mt-6 text-right`}>
                    <Button type={'button'} isSecondary css={tw`mr-2`} onClick={() => setDeployVisible(false)}>
                        Cancel
                    </Button>
                    <Button type={'submit'} color={'green'} onClick={() => onDeploy()}>
                        Deploy Backup
                    </Button>
                </div>
            </Modal>
            <Modal visible={visible} onDismissed={() => setVisible(false)}>
                <FlashMessageRender byKey={'database:backup:delete'} css={tw`mb-6`} />
                <h2 css={tw`text-2xl mb-6`}>Confirm database deletion</h2>
                <p css={tw`text-sm`}>Deleting a database backup is a permanent action.</p>
                <div css={tw`mt-6 text-right`}>
                    <Button type={'button'} isSecondary css={tw`mr-2`} onClick={() => setVisible(false)}>
                        Cancel
                    </Button>
                    <Button type={'submit'} color={'red'} onClick={() => onDelete()}>
                        Delete Backup
                    </Button>
                </div>
            </Modal>
            <GreyRowBox $hoverable={false} css={tw`mb-2`}>
                <div css={tw`hidden md:block`}>
                    <FontAwesomeIcon icon={faServer} fixedWidth />
                </div>
                <div css={tw`flex-1 ml-4`}>
                    <p css={tw`text-lg`}>{backup.name}</p>
                </div>
                <div css={tw`ml-8 text-center hidden md:block`}>
                    <p css={tw`text-sm`}>{backup.database === null ? 'Database Deleted' : backup.database}</p>
                    <p css={tw`mt-1 text-2xs text-neutral-500 uppercase select-none`}>Database</p>
                </div>
                <div css={tw`ml-8 text-center hidden md:block`}>
                    <p css={tw`text-sm`}>{backup.created_at}</p>
                    <p css={tw`mt-1 text-2xs text-neutral-500 uppercase select-none`}>Created At</p>
                </div>
                <div css={tw`ml-8`}>
                    <a
                        href={`/api/client/servers/${uuid}/databases/backup/${backup.id}/download`}
                        target={'_blank'}
                        rel={'noreferrer'}
                    >
                        <Button color={'green'} isSecondary css={tw`mr-2`}>
                            <FontAwesomeIcon icon={faDownload} fixedWidth />
                        </Button>
                    </a>
                    {backup.database !== null && (
                        <Button
                            color={'primary'}
                            isSecondary
                            onClick={() => {
                                setDeployId(backup.id);
                                setDeployVisible(true);
                            }}
                            css={tw`mr-2`}
                        >
                            <FontAwesomeIcon icon={faBoxOpen} fixedWidth />
                        </Button>
                    )}
                    <Button
                        color={'red'}
                        isSecondary
                        onClick={() => {
                            setDeleteId(backup.id);
                            setVisible(true);
                        }}
                    >
                        <FontAwesomeIcon icon={faTrashAlt} fixedWidth />
                    </Button>
                </div>
            </GreyRowBox>
        </>
    );
};
