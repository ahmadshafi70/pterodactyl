import React, { useEffect, useMemo, useState } from 'react';
import { ServerContext } from '@/state/server';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import { Button } from '@/components/elements/button/index';
import getTypes, { MinecraftVersionProviderType } from './api/getTypes';
import Spinner from '@/components/elements/Spinner';
import Tooltip from '@/components/elements/tooltip/Tooltip';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faArrowLeft, faExclamationTriangle, faSkull, faBoxes } from '@fortawesome/free-solid-svg-icons';
import getVersionsForType, { MinecraftVersionBuild, MinecraftVersionBuilds } from './api/getVersionsForType';
import getBuildsForVersionForType from './api/getBuildsForVersionForType';
import getInstalled from './api/getInstalled';
import installVersion from './api/installVersion';
import { Dialog } from '@/components/elements/dialog';
import { Alert } from '@/components/elements/alert';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Select from '@/components/elements/Select';
import Switch from '@/components/elements/Switch';
import { useLocation } from 'react-router';

export default function VersionChangerContainer() {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);

    const location = useLocation();

    const [type, setType] = useState<string>();
    const [types, setTypes] = useState<Record<string, MinecraftVersionProviderType[]>>({});
    const [installed, setInstalled] = useState<{
        build: MinecraftVersionBuild;
        latest: MinecraftVersionBuild;
    } | null>();
    const [installedType, setInstalledType] = useState<MinecraftVersionProviderType | null>();
    const [typeBuilds, setTypeBuilds] = useState<MinecraftVersionBuilds[]>();
    const [versionBuilds, setVersionBuilds] = useState<MinecraftVersionBuild[]>();
    const [selectedVersion, setSelectedVersion] = useState<MinecraftVersionBuilds>();
    const [selectedBuild, setSelectedBuild] = useState<MinecraftVersionBuild>();
    const [deleteServerFiles, setDeleteServerFiles] = useState(false);
    const [acceptEula, setAcceptEula] = useState(false);
    const [showSnapshots, setShowSnapshots] = useState(false);
    const [isLoading, setIsLoading] = useState(false);

    const allTypes = useMemo(() => Object.values(types).reduce((acc, val) => acc.concat(val), []), [types]);

    useEffect(() => {
        if (Object.keys(types).length) {
            const params = new URLSearchParams(location.search);

            setType(allTypes.find((t) => t.identifier === params.get('type')?.toUpperCase())?.identifier ?? undefined);
        }
    }, [types, location]);

    useEffect(() => {
        const searchParams = new URLSearchParams(location.search);

        if (!type) {
            searchParams.delete('type');
        } else {
            searchParams.set('type', type.toUpperCase());
        }

        window.history.pushState(
            {},
            '',
            `${location.pathname}${searchParams?.toString() ? '?' : ''}${searchParams?.toString()}`
        );
    }, [type]);

    const submit = () => {
        if (!selectedBuild) {
            return;
        }

        setIsLoading(true);

        installVersion(uuid, selectedBuild.id, deleteServerFiles, acceptEula)
            .then(() => {
                getInstalled(uuid)
                    .then(setInstalled)
                    .catch((error) => {
                        console.error(error);
                    });
            })
            .catch((error) => {
                console.error(error);
            })
            .finally(() => {
                setIsLoading(false);
                setType(undefined);
                setSelectedVersion(undefined);
                setSelectedBuild(undefined);
            });
    };

    const filterVersion = (version: MinecraftVersionBuilds, __: number, _: any[], ignoreSnapshot = false): boolean => {
        if (ignoreSnapshot && version.type && !showSnapshots && version.type === 'SNAPSHOT') {
            return false;
        }

        if (!version.type) return true;

        return true;
    };

    useEffect(() => {
        getTypes(uuid)
            .then(setTypes)
            .catch((error) => {
                console.error(error);
            });

        getInstalled(uuid)
            .then(setInstalled)
            .catch((error) => {
                console.error(error);
            });
    }, []);

    useEffect(() => {
        if (!types || installed === undefined) {
            return;
        }

        if (installed === null) {
            setInstalledType(null);
        } else {
            setInstalledType(allTypes.find((t) => t.identifier === installed.build.type) || null);
        }
    }, [types, installed]);

    useEffect(() => {
        if (!type) {
            return;
        }

        const typeData = allTypes.find((t) => t.identifier === type);
        if (!typeData) {
            return;
        }

        setSelectedVersion(undefined);
        setSelectedBuild(undefined);
        setTypeBuilds(undefined);

        getVersionsForType(uuid, typeData.identifier).then(setTypeBuilds);
    }, [type]);

    useEffect(() => {
        if (!selectedVersion || !type) {
            return;
        }

        setVersionBuilds(undefined);
        setSelectedBuild(selectedVersion.latest);

        getBuildsForVersionForType(uuid, type, selectedVersion.version).then(setVersionBuilds);
    }, [type, selectedVersion]);

    if (!Object.keys(types).length || installed === undefined) {
        return <Spinner size={'large'} centered />;
    }

    return (
        <ServerContentBlock title={'Versions'}>
            <SpinnerOverlay visible={isLoading} fixed />

            <Dialog
                title={`Install ${allTypes.find((t) => t.identifier === type)?.name} ${selectedVersion?.version}`}
                open={!!selectedVersion && !isLoading}
                onClose={() => setSelectedVersion(undefined)}
            >
                <div className={'flex flex-col'}>
                    {!versionBuilds || !selectedVersion ? (
                        <div className={'flex flex-row justify-center items-center h-20'}>
                            <Spinner size={'large'} />
                        </div>
                    ) : (
                        <>
                            {versionBuilds.every((b) => b.experimental) && (
                                <Alert type={'danger'} className={'mb-3'}>
                                    This version is experimental and may contain bugs or other issues. Please make sure
                                    to create a backup of your server before installing this version.
                                </Alert>
                            )}

                            <div className={'flex flex-col h-full'}>
                                {type?.toUpperCase() !== 'VANILLA' && (
                                    <Select
                                        className={'mb-4'}
                                        onChange={(e) =>
                                            setSelectedBuild(
                                                versionBuilds.find((b) => b.id === parseInt(e.target.value))
                                            )
                                        }
                                    >
                                        {versionBuilds.some((b) => !b.experimental) && (
                                            <optgroup label={`Stable Builds (${versionBuilds.filter((b) => !b.experimental).length})`}>
                                                {versionBuilds
                                                    .filter((b) => !b.experimental)
                                                    .map((build) => (
                                                        <option key={build.id} value={build.id}>
                                                            Build {build.name}
                                                        </option>
                                                    ))}
                                            </optgroup>
                                        )}
                                        {versionBuilds.some((b) => b.experimental) && (
                                            <optgroup label={`Experimental Builds (${versionBuilds.filter((b) => b.experimental).length})`}>
                                                {versionBuilds
                                                    .filter((b) => b.experimental)
                                                    .map((build) => (
                                                        <option key={build.id} value={build.id}>
                                                            Build {build.name}
                                                        </option>
                                                    ))}
                                            </optgroup>
                                        )}
                                    </Select>
                                )}
                            </div>
                            <div>
                                <div className={'bg-neutral-700 border border-neutral-800 shadow-inner p-4 rounded'}>
                                    <Switch
                                        name={'delete_server_files'}
                                        label={'Wipe Server Files'}
                                        description={
                                            'This will delete all files on your server before installing the new version. This cannot be undone.'
                                        }
                                        defaultChecked={deleteServerFiles}
                                        onChange={(e) => setDeleteServerFiles(e.target.checked)}
                                        readOnly={isLoading}
                                    />
                                </div>

                                <div className={'bg-neutral-700 border border-neutral-800 shadow-inner p-4 rounded mt-4'}>
                                    <Switch
                                        name={'accept_eula'}
                                        label={'Accept EULA'}
                                        description={
                                            'By enabling this option you confirm that you have read and accept the Minecraft EULA. (https://minecraft.net/eula)'
                                        }
                                        defaultChecked={acceptEula}
                                        onChange={(e) => setAcceptEula(e.target.checked)}
                                        readOnly={isLoading}
                                    />
                                </div>
                            </div>
                        </>
                    )}
                </div>
                <Dialog.Footer>
                    <Button.Text onClick={() => setSelectedVersion(undefined)} disabled={isLoading}>
                        Cancel
                    </Button.Text>
                    <Button.Danger onClick={submit} disabled={isLoading}>
                        Install
                    </Button.Danger>
                </Dialog.Footer>
            </Dialog>

            {installed && installedType && (
                <>
                    <div
                        className={`versionchanger-installed-row relative border-l-4 ${
                            installed.build.id !== installed.latest.id ? 'border-yellow-500' : 'border-green-500'
                        } bg-gray-700 p-3 rounded-md w-full min-w-[20rem] flex flex-row justify-between items-center`}
                    >
                        <img src={installedType.icon} className={'rounded object-cover select-none w-16 h-16 mr-2'} />
                        <div className={'flex flex-row pl-2 justify-between w-full'}>
                            <div className={'flex flex-col h-full justify-between w-full'}>
                                <div className={'flex flex-col'}>
                                    <h2 className={'break-words w-auto h-auto text-xl'}>
                                        Currently running {installedType.name}
                                        {installedType.experimental && (
                                            <Tooltip content={'Experimental'}>
                                                <span className={'ml-2 text-yellow-500'}>
                                                    <FontAwesomeIcon icon={faExclamationTriangle} />
                                                </span>
                                            </Tooltip>
                                        )}
                                        {installedType.deprecated && (
                                            <Tooltip content={'Deprecated'}>
                                                <span className={'ml-2 text-red-500'}>
                                                    <FontAwesomeIcon icon={faSkull} />
                                                </span>
                                            </Tooltip>
                                        )}
                                    </h2>
                                    {installed.build.versionId ? (
                                        <p>Installed Minecraft Version: {installed.build.versionId}</p>
                                    ) : (
                                        <p>Installed Project Version: {installed.build.projectVersionId}</p>
                                    )}
                                    {installed.build.type !== 'VANILLA' && (
                                        <p>Installed Build: {installed.build.name}</p>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    {installed.build.id !== installed.latest.id && (
                        <Alert type={'warning'} className={'my-2'}>
                            Your server is currently running an outdated version of {installedType.name}{' '}
                            {installed.build.versionId ?? installed.build.projectVersionId}. The latest build is{' '}
                            {installed.latest.name}.
                        </Alert>
                    )}
                </>
            )}

            <div className={'w-full flex flex-col'}>
                {!type ? (
                    <>
                        {Object.entries(types).map(([category, types]) => (
                            <div key={category}>
                                <div className={'flex flex-row items-center my-1'}>
                                    <p className={'text-neutral-300 text-sm mr-2 ml-1'}>{category}</p>
                                    <div className={'flex-1 mx-1 my-4 border border-gray-700 border-b'} />
                                </div>

                                <div className={'w-full grid grid-cols-[repeat(auto-fill,minmax(20rem,1fr))] gap-2'}>
                                    {types.map((type) => (
                                        <div
                                            key={type.identifier}
                                            onClick={() => setType(type.identifier)}
                                            className={`nebula-animation versionchanger-type-row relative border-l-4 ${
                                                installedType?.identifier === type.identifier
                                                    ? installed?.build.id !== installed?.latest.id
                                                        ? 'border-yellow-500'
                                                        : 'border-green-500'
                                                    : 'border-gray-500'
                                            } bg-gray-700 cursor-pointer hover:bg-gray-600 transition-all p-3 rounded-md w-full min-w-[20rem] select-none flex flex-row justify-between items-center`}
                                        >
                                            <img src={type.icon} className={'rounded object-cover w-16 h-16 mr-2'} />
                                            <div className={'flex flex-row pl-2 justify-between w-full'}>
                                                <div className={'flex flex-col h-full justify-between w-full'}>
                                                    <div className={'flex flex-col'}>
                                                        <h2 className={'break-words w-auto h-auto text-xl'}>
                                                            {type.name}
                                                            {type.experimental && (
                                                                <Tooltip content={'Experimental'}>
                                                                    <span className={'ml-2 text-yellow-500'}>
                                                                        <FontAwesomeIcon icon={faExclamationTriangle} />
                                                                    </span>
                                                                </Tooltip>
                                                            )}
                                                            {type.deprecated && (
                                                                <Tooltip content={'Deprecated'}>
                                                                    <span className={'ml-2 text-red-500'}>
                                                                        <FontAwesomeIcon icon={faSkull} />
                                                                    </span>
                                                                </Tooltip>
                                                            )}
                                                        </h2>
                                                        {type.versions.minecraft > 0 ? (
                                                            <p>{type.versions.minecraft} Minecraft versions</p>
                                                        ) : (
                                                            <p>{type.versions.project} Project versions</p>
                                                        )}
                                                        <p>
                                                            {type.builds} {type.builds === 1 ? 'Build' : 'Builds'}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        ))}
                    </>
                ) : !typeBuilds ? (
                    <span className={'col-span-full'}>
                        <Spinner size={'large'} centered />
                    </span>
                ) : (
                    <div className={'mb-2'}>
                        <div className={'flex flex-row items-center my-1'}>
                            <p className={'text-neutral-300 text-sm mr-2 ml-1'}>
                                {allTypes.find((t) => t.identifier === type)?.name}
                            </p>
                            <div className={'flex-1 mx-1 my-4 border border-gray-700 border-b'} />
                        </div>
                        <div className={'w-full grid grid-cols-[repeat(auto-fill,minmax(20rem,1fr))] gap-2'}>
                            <div
                                className={`nebula-animation versionchanger-back-row border-l-4 border-gray-500 bg-gray-700 cursor-pointer hover:bg-gray-600 transition-all p-3 rounded-md w-full min-w-[20rem] flex flex-row justify-between items-center`}
                                onClick={() => setType(undefined)}
                            >
                                <span className={'w-10 h-10 mr-2 flex flex-row justify-center items-center'}>
                                    <FontAwesomeIcon icon={faArrowLeft} size={'2x'} />
                                </span>
                                <div className={'flex flex-row pl-2 justify-between w-full select-none'}>
                                    <div className={'h-full w-full'}>
                                        <h2 className={'break-words w-auto h-auto'}>Go Back</h2>
                                    </div>
                                </div>
                            </div>

                            {typeBuilds.some((build) => build.type === 'SNAPSHOT') && (
                                <div
                                    className={`nebula-animation versionchanger-snapshots-row border-l-4 ${
                                        showSnapshots ? 'border-green-500' : 'border-gray-500'
                                    } bg-gray-700 cursor-pointer hover:bg-gray-600 transition-all p-3 rounded-md w-full min-w-[20rem] select-none flex flex-row justify-between items-center`}
                                    onClick={() => setShowSnapshots((v) => !v)}
                                >
                                    <span className={'w-10 h-10 mr-2 flex flex-row justify-center items-center'}>
                                        <FontAwesomeIcon icon={faBoxes} size={'2x'} />
                                    </span>
                                    <div className={'flex flex-row pl-2 justify-between w-full'}>
                                        <div className={'h-full w-full'}>
                                            <h2 className={'break-words w-auto h-auto'}>Show Snapshot Versions</h2>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {typeBuilds
                                .filter((v, i, arr) => filterVersion(v, i, arr, true))
                                .map((version) => (
                                    <div
                                        key={version.version}
                                        className={`nebula-animation versionchanger-version-row border-l-4 ${
                                            (installed?.build.versionId ?? installed?.build.projectVersionId) ===
                                                version.version && installedType?.identifier === version.latest.type
                                                ? installed?.build.id !== installed?.latest.id
                                                    ? 'border-yellow-500'
                                                    : 'border-green-500'
                                                : 'border-gray-500'
                                        } bg-gray-700 cursor-pointer hover:bg-gray-600 transition-all p-3 rounded-md w-full min-w-[20rem] select-none flex flex-row justify-between items-center`}
                                        onClick={() => setSelectedVersion(version)}
                                    >
                                        <img
                                            src={allTypes.find((t) => t.identifier === type)?.icon}
                                            className={'rounded object-cover w-10 h-10 mr-2'}
                                        />
                                        <div className={'flex flex-row pl-2 justify-between w-full'}>
                                            <div className={'h-full w-full'}>
                                                <div className={'grid grid-cols-5'}>
                                                    <h2
                                                        className={
                                                            'break-words w-auto h-auto text-white text-xl col-span-3'
                                                        }
                                                    >
                                                        {version.version}
                                                        <p className={'text-gray-300 text-base -mt-1'}>
                                                            {version.type}
                                                        </p>
                                                    </h2>
                                                    <p className={'my-auto col-span-2 text-right pr-1'}>
                                                        {version.builds} {version.builds === 1 ? 'Build' : 'Builds'}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                        </div>
                    </div>
                )}
            </div>
        </ServerContentBlock>
    );
}
