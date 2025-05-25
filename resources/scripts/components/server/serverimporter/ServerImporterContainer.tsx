import React, { useEffect, useMemo, useState } from 'react';
import { ServerContext } from '@/state/server';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import Spinner from '@/components/elements/Spinner';
import useSWR from 'swr';
import useFlash from '@/plugins/useFlash';
import getProfiles, { CredentialProfile, ServerProfile } from './api/getProfiles';
import { faEye, faEyeSlash, faPlus } from '@fortawesome/free-solid-svg-icons';
import { Button } from '@/components/elements/button/index';
import { Dialog } from '@/components/elements/dialog';
import Input from '@/components/elements/Input';
import Label from '@/components/elements/Label';
import Select from '@/components/elements/Select';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import FlashMessageRender from '@/components/FlashMessageRender';
import deleteCredentialProfileApi from './api/deleteCredentialProfile';
import createCredentialProfile from './api/createCredentialProfile';
import updateCredentialProfile from './api/updateCredentialProfile';
import deleteServerProfileApi from './api/deleteServerProfile';
import createServerProfile from './api/createServerProfile';
import updateServerProfile from './api/updateServerProfile';
import testCredentials from './api/testCredentials';
import importServer from './api/importServer';
import Switch from '@/components/elements/Switch';
import classNames from 'classnames';

export default function ServerImporterContainer() {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const setServer = ServerContext.useStoreActions((actions) => actions.server.setServerFromState);
    const [viewCredentialProfile, setViewCredentialProfile] = useState<CredentialProfile>();
    const [viewServerProfile, setViewServerProfile] = useState<ServerProfile>();
    const [deleteCredentialProfile, setDeleteCredentialProfile] = useState<CredentialProfile>();
    const [deleteServerProfile, setDeleteServerProfile] = useState<ServerProfile>();
    const [confirmImport, setConfirmImport] = useState(false);
    const [create, setCreate] = useState(false);
    const [view, setView] = useState<'credentials' | 'servers'>('credentials');
    const [isLoading, setIsLoading] = useState(false);
    const { clearFlashes, clearAndAddHttpError } = useFlash();

    const [showPassword, setShowPassword] = useState(false);
    const [_name, setName] = useState<string>('');
    const [username, setUsername] = useState<string>();
    const [password, setPassword] = useState<string>('');
    const [host, setHost] = useState<string>('');
    const [port, setPort] = useState<number>(22);
    const [_mode, setMode] = useState<'sftp' | 'ftp'>('sftp');

    const [importHost, setImportHost] = useState('');
    const [importPort, setImportPort] = useState(22);
    const [importMode, setImportMode] = useState<'sftp' | 'ftp'>('sftp');
    const [importUsername, setImportUsername] = useState('');
    const [importPassword, setImportPassword] = useState('');
    const [importFrom, setImportFrom] = useState('/');
    const [importTo, setImportTo] = useState('/');
    const [importDeleteFiles, setImportDeleteFiles] = useState(false);
    const [validatedCredentials, setValidatedCredentials] = useState('');

    const isValidatedCredentials = useMemo(() => {
        return (
            validatedCredentials ===
            JSON.stringify({ importHost, importPort, importUsername, importPassword, importMode, importFrom })
        );
    }, [validatedCredentials, importHost, importPort, importUsername, importPassword, importMode, importFrom]);

    const { data, mutate } = useSWR(['importer', 'profiles', uuid], () => getProfiles(uuid), {
        refreshInterval: 10000,
    });

    useEffect(() => {
        clearFlashes('importer:main');
    }, []);

    useEffect(() => {
        if (viewCredentialProfile) {
            setName(viewCredentialProfile.attributes.name);
            setUsername(viewCredentialProfile.attributes.username || undefined);
            setPassword(viewCredentialProfile.attributes.password);
        } else {
            setName('');
            setUsername(undefined);
            setPassword('');
        }
    }, [viewCredentialProfile]);

    useEffect(() => {
        if (viewServerProfile) {
            setName(viewServerProfile.attributes.name);
            setHost(viewServerProfile.attributes.host);
            setPort(viewServerProfile.attributes.port);
            setMode(viewServerProfile.attributes.mode);
        } else {
            setName('');
            setHost('');
            setPort(22);
            setMode('sftp');
        }
    }, [viewServerProfile]);

    useEffect(() => {
        if (create) {
            setViewCredentialProfile(undefined);
            setViewServerProfile(undefined);
        }
    }, [create]);

    useEffect(() => {
        if (importHost.includes('://')) {
            const protocol = importHost.split('://')[0];
            let rawHost = importHost.split('://')[1];

            if (rawHost.includes('@')) {
                const [username, host] = rawHost.split('@');

                if (username.includes(':')) {
                    const [user, pass] = username.split(':');
                    setImportUsername(user);
                    setImportPassword(decodeURIComponent(pass));
                } else {
                    setImportUsername(username);
                }

                rawHost = host;
            }

            if (rawHost.includes(':')) {
                const [host, port] = rawHost.split(':');
                setImportHost(host);
                setImportPort(parseInt(port));
            } else {
                setImportHost(rawHost);
            }

            if (protocol.toLowerCase() === 'sftp') {
                setImportMode('sftp');
            } else if (protocol.toLowerCase() === 'ftp') {
                setImportMode('ftp');
            }
        }
    }, [importHost]);

    useEffect(() => {
        if (port > 65535) {
            setPort(65535);
        }

        if (port < 1) {
            setPort(1);
        }
    }, [port]);

    useEffect(() => {
        if (importPort > 65535) {
            setImportPort(65535);
        }

        if (importPort < 1) {
            setImportPort(1);
        }
    }, [importPort]);

    if (!data) {
        return <Spinner size={'large'} centered />;
    }

    return (
        <ServerContentBlock title={'Importer'}>
            <Dialog.Confirm
                open={confirmImport}
                confirm={'Import'}
                title={'Confirm Import'}
                onConfirmed={() => {
                    importServer(
                        uuid,
                        importMode,
                        importUsername,
                        importPassword,
                        importHost,
                        importPort,
                        importFrom,
                        importTo,
                        importDeleteFiles
                    )
                        .then(() => {
                            setConfirmImport(false);
                            clearFlashes('importer:import');
                            setServer((old) => ({ ...old, status: 'installing' }));
                        })
                        .catch((error) => {
                            clearAndAddHttpError({ key: 'importer:import', error });
                            setConfirmImport(false);
                        });
                }}
                onClose={() => setConfirmImport(false)}
            >
                Are you sure you want to import server files using the selected settings? This will kill the server
                before starting the process.
                <div className={'bg-neutral-700 border border-neutral-800 shadow-inner p-4 rounded mt-4'}>
                    <Switch
                        name={'delete_server_files'}
                        label={'Wipe Server Files'}
                        description={
                            'This will delete all files on your server before installing the new version. This cannot be undone.'
                        }
                        defaultChecked={importDeleteFiles}
                        onChange={(e) => setImportDeleteFiles(e.target.checked)}
                        readOnly={isLoading}
                    />
                </div>
            </Dialog.Confirm>

            <Dialog.Confirm
                open={!!deleteCredentialProfile}
                confirm={'Delete'}
                title={`Delete Credential Profile ${deleteCredentialProfile?.attributes.name}`}
                onConfirmed={() => {
                    deleteCredentialProfileApi(uuid, deleteCredentialProfile?.attributes.id ?? 0)
                        .then(() => {
                            mutate(
                                (old) => ({
                                    ...old,
                                    credentials: old.credentials.filter(
                                        (profile) => profile.attributes.id !== deleteCredentialProfile!.attributes.id
                                    ),
                                }),
                                false
                            );

                            setDeleteCredentialProfile(undefined);
                            setViewCredentialProfile(undefined);
                        })
                        .catch(console.error);
                }}
                onClose={() => {
                    setViewCredentialProfile(deleteCredentialProfile);
                    setDeleteCredentialProfile(undefined);
                }}
            >
                Are you sure you want to delete the credential profile{' '}
                <strong>{deleteCredentialProfile?.attributes.name}</strong>? This action cannot be undone.
            </Dialog.Confirm>
            <Dialog.Confirm
                open={!!deleteServerProfile}
                confirm={'Delete'}
                title={`Delete Server Profile ${deleteServerProfile?.attributes.name}`}
                onConfirmed={() => {
                    deleteServerProfileApi(uuid, deleteServerProfile?.attributes.id ?? 0)
                        .then(() => {
                            mutate(
                                (old) => ({
                                    ...old,
                                    servers: old.servers.filter(
                                        (profile) => profile.attributes.id !== deleteServerProfile!.attributes.id
                                    ),
                                }),
                                false
                            );

                            setDeleteServerProfile(undefined);
                            setViewServerProfile(undefined);
                        })
                        .catch(console.error);
                }}
                onClose={() => {
                    setViewServerProfile(deleteServerProfile);
                    setDeleteServerProfile(undefined);
                }}
            >
                Are you sure you want to delete the server profile{' '}
                <strong>{deleteServerProfile?.attributes.name}</strong>? This action cannot be undone.
            </Dialog.Confirm>

            <FlashMessageRender byKey={'importer:main'} className={'mb-4'} />

            <div className={'md:grid md:grid-cols-3 md:gap-2 md:space-y-0 flex flex-col space-y-2'}>
                <div className={'serverimporter-import-profile-box bg-gray-700 p-3 rounded-md'}>
                    <Label>Profiles</Label>

                    <div className={'flex flex-row space-x-2'}>
                        <Select value={view} onChange={(e) => setView(e.target.value as 'credentials' | 'servers')}>
                            <option value={'credentials'}>Credentials</option>
                            <option value={'servers'}>Servers</option>
                        </Select>
                        <Button.Text onClick={() => setCreate(true)}>
                            <FontAwesomeIcon icon={faPlus} />
                        </Button.Text>
                    </div>

                    <div className={'flex-1 my-4 border border-gray-500 border-b'} />

                    {view === 'credentials' ? (
                        data.credentials.length === 0 ? (
                            <p className={'text-neutral-400 text-center mt-2'}>
                                No credential profiles have been created.
                            </p>
                        ) : (
                            data.credentials.map((profile, i) => (
                                <div
                                    className={classNames(
                                        'serverimporter-credential-profile-row mt-2 cursor-pointer border-transparent border transition-all bg-gray-800 p-3 rounded-md flex flex-col',
                                        viewCredentialProfile?.attributes.id === profile.attributes.id
                                            ? 'border-l-4 border-l-gray-500 cursor-not-allowed'
                                            : 'hover:border-gray-500'
                                    )}
                                    onClick={() => {
                                        setViewCredentialProfile(profile);
                                        setViewServerProfile(undefined);
                                        setCreate(false);
                                    }}
                                    key={i}
                                >
                                    {profile.attributes.name}
                                </div>
                            ))
                        )
                    ) : data.servers.length === 0 ? (
                        <p className={'text-neutral-400 text-center mt-2'}>No server profiles have been created.</p>
                    ) : (
                        data.servers.map((profile, i) => (
                            <div
                                className={classNames(
                                    'serverimporter-server-profile-row mt-2 cursor-pointer border-transparent border transition-all bg-gray-800 p-3 rounded-md flex flex-col',
                                    viewServerProfile?.attributes.id === profile.attributes.id
                                        ? 'hover:border-transparent hover:border-l-gray-500 border-l-4 border-l-gray-500 cursor-not-allowed'
                                        : 'hover:border-gray-500'
                                )}
                                onClick={() => {
                                    setViewServerProfile(profile);
                                    setViewCredentialProfile(undefined);
                                    setCreate(false);
                                }}
                                key={i}
                            >
                                {profile.attributes.name}
                            </div>
                        ))
                    )}
                </div>

                <div
                    className={'serverimporter-import-main-box md:col-span-2 md:row-span-1 bg-gray-700 p-3 rounded-md'}
                >
                    {create ? (
                        view === 'credentials' ? (
                            <>
                                <form
                                    id={'create-credential-profile'}
                                    className={'flex flex-col h-full justify-between'}
                                    onSubmit={(e) => {
                                        e.preventDefault();
                                        setIsLoading(true);

                                        createCredentialProfile(uuid, _name, username ?? null, password)
                                            .then((profile) => {
                                                mutate(
                                                    (old) => ({ ...old, credentials: [...old.credentials, profile] }),
                                                    false
                                                );

                                                clearFlashes('importer:main');
                                                setViewCredentialProfile(profile);
                                                setCreate(false);
                                            })
                                            .catch((error) => clearAndAddHttpError({ key: 'importer:main', error }))
                                            .finally(() => setIsLoading(false));
                                    }}
                                >
                                    <div className={'flex flex-col'}>
                                        <Label>New Credential Profile</Label>

                                        <div className={'flex flex-row w-full items-center space-x-2 mt-4'}>
                                            <div className={'w-full'}>
                                                <Label>Name</Label>
                                                <Input
                                                    value={_name}
                                                    placeholder={'Name'}
                                                    onChange={(e) => setName(e.target.value)}
                                                    disabled={isLoading}
                                                />
                                            </div>
                                            <div className={'w-full'}>
                                                <Label>Username</Label>
                                                <Input
                                                    value={username ?? ''}
                                                    placeholder={'Username'}
                                                    onChange={(e) => setUsername(e.target.value)}
                                                    disabled={isLoading}
                                                />
                                            </div>
                                        </div>

                                        <div className={'w-full mt-2'}>
                                            <Label>Password</Label>
                                            <Input
                                                type={showPassword ? 'text' : 'password'}
                                                placeholder={'Password'}
                                                value={password}
                                                onChange={(e) => setPassword(e.target.value)}
                                                disabled={isLoading}
                                            />
                                        </div>
                                    </div>

                                    <div
                                        className={
                                            'flex flex-col w-full md:flex-row md:space-y-0 space-y-2 items-center md:justify-between mt-4'
                                        }
                                    >
                                        <div className={'flex flex-row w-full items-center md:w-auto space-x-2'}>
                                            <Button.Text
                                                type={'button'}
                                                disabled={isLoading}
                                                onClick={() => setCreate(false)}
                                            >
                                                Cancel
                                            </Button.Text>
                                            <Button.Text
                                                type={'button'}
                                                onClick={() => setShowPassword((s) => !s)}
                                                className={'w-full md:w-auto'}
                                            >
                                                {showPassword ? 'Hide' : 'Show'} Password
                                            </Button.Text>
                                        </div>
                                        <Button type={'submit'} disabled={isLoading} className={'w-full md:w-auto'}>
                                            Create
                                        </Button>
                                    </div>
                                </form>
                            </>
                        ) : (
                            <>
                                <form
                                    id={'create-server-profile'}
                                    className={'flex flex-col h-full justify-between'}
                                    onSubmit={(e) => {
                                        e.preventDefault();
                                        setIsLoading(true);

                                        createServerProfile(uuid, _name, _mode, host, port)
                                            .then((profile) => {
                                                mutate(
                                                    (old) => ({ ...old, servers: [...old.servers, profile] }),
                                                    false
                                                );

                                                clearFlashes('importer:main');
                                                setViewServerProfile(profile);
                                                setCreate(false);
                                            })
                                            .catch((error) => clearAndAddHttpError({ key: 'importer:main', error }))
                                            .finally(() => setIsLoading(false));
                                    }}
                                >
                                    <div className={'flex flex-col'}>
                                        <Label>New Server Profile</Label>

                                        <div className={'flex flex-row w-full items-center space-x-2 mt-4'}>
                                            <div className={'w-full'}>
                                                <Label>Name</Label>
                                                <Input
                                                    value={_name}
                                                    placeholder={'Name'}
                                                    onChange={(e) => setName(e.target.value)}
                                                    disabled={isLoading}
                                                />
                                            </div>
                                            <div className={'w-full'}>
                                                <Label>Host</Label>
                                                <Input
                                                    value={host}
                                                    placeholder={'Host'}
                                                    onChange={(e) => setHost(e.target.value)}
                                                    disabled={isLoading}
                                                />
                                            </div>
                                        </div>

                                        <div className={'flex flex-row w-full items-center space-x-2 mt-2'}>
                                            <div className={'w-full'}>
                                                <Label>Port</Label>
                                                <Input
                                                    type={'number'}
                                                    value={port}
                                                    placeholder={'Port'}
                                                    min={1}
                                                    max={65535}
                                                    onChange={(e) => setPort(e.target.valueAsNumber)}
                                                    disabled={isLoading}
                                                />
                                            </div>
                                            <div className={'w-full'}>
                                                <Label>Mode</Label>
                                                <Select
                                                    value={_mode}
                                                    onChange={(e) => setMode(e.target.value as 'sftp' | 'ftp')}
                                                    disabled={isLoading}
                                                >
                                                    <option value={'sftp'}>SFTP</option>
                                                    <option value={'ftp'}>FTP</option>
                                                </Select>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        className={
                                            'flex flex-col w-full md:flex-row md:space-y-0 space-y-2 items-center md:justify-between mt-4'
                                        }
                                    >
                                        <Button.Text
                                            type={'button'}
                                            disabled={isLoading}
                                            onClick={() => setCreate(false)}
                                            className={'w-full md:w-auto'}
                                        >
                                            Cancel
                                        </Button.Text>
                                        <Button type={'submit'} disabled={isLoading} className={'w-full md:w-auto'}>
                                            Create
                                        </Button>
                                    </div>
                                </form>
                            </>
                        )
                    ) : viewCredentialProfile ? (
                        <>
                            <form
                                id={'edit-credential-profile'}
                                className={'flex flex-col h-full justify-between'}
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    setIsLoading(true);

                                    updateCredentialProfile(
                                        uuid,
                                        viewCredentialProfile.attributes.id,
                                        _name,
                                        username ?? null,
                                        password
                                    )
                                        .then(() => {
                                            mutate(
                                                (old) => ({
                                                    ...old,
                                                    credentials: old.credentials.map((profile) =>
                                                        profile.attributes.id === viewCredentialProfile.attributes.id
                                                            ? {
                                                                  ...profile,
                                                                  attributes: {
                                                                      ...profile.attributes,
                                                                      name: _name,
                                                                      username: username ?? null,
                                                                      password,
                                                                  },
                                                              }
                                                            : profile
                                                    ),
                                                }),
                                                false
                                            );

                                            clearFlashes('importer:main');
                                        })
                                        .catch((error) => clearAndAddHttpError({ key: 'importer:main', error }))
                                        .finally(() => setIsLoading(false));
                                }}
                            >
                                <div className={'flex flex-col'}>
                                    <Label>Edit Credential Profile</Label>

                                    <div className={'flex flex-row w-full items-center space-x-2 mt-4'}>
                                        <div className={'w-full'}>
                                            <Label>Name</Label>
                                            <Input
                                                value={_name}
                                                placeholder={'Name'}
                                                onChange={(e) => setName(e.target.value)}
                                                disabled={isLoading}
                                            />
                                        </div>
                                        <div className={'w-full'}>
                                            <Label>Username</Label>
                                            <Input
                                                value={username ?? ''}
                                                placeholder={'Username'}
                                                onChange={(e) => setUsername(e.target.value)}
                                                disabled={isLoading}
                                            />
                                        </div>
                                    </div>

                                    <div className={'w-full mt-2'}>
                                        <Label>Password</Label>
                                        <Input
                                            type={showPassword ? 'text' : 'password'}
                                            placeholder={'Password'}
                                            value={password}
                                            onChange={(e) => setPassword(e.target.value)}
                                            disabled={isLoading}
                                        />
                                    </div>
                                </div>

                                <div
                                    className={
                                        'flex flex-col w-full md:flex-row md:space-y-0 space-y-2 items-center md:justify-between mt-4'
                                    }
                                >
                                    <div className={'flex flex-row w-full items-center md:w-auto space-x-2'}>
                                        <Button.Text
                                            type={'button'}
                                            disabled={isLoading}
                                            onClick={() => setViewCredentialProfile(undefined)}
                                        >
                                            Cancel
                                        </Button.Text>
                                        <Button.Text
                                            type={'button'}
                                            onClick={() => setShowPassword((s) => !s)}
                                            className={'w-full md:w-auto'}
                                        >
                                            {showPassword ? 'Hide' : 'Show'} Password
                                        </Button.Text>
                                    </div>
                                    <div className={'flex flex-row w-full items-center md:w-auto space-x-2'}>
                                        <Button.Text
                                            type={'button'}
                                            onClick={() => {
                                                setViewCredentialProfile(undefined);

                                                setImportUsername(username ?? '');
                                                setImportPassword(password);
                                            }}
                                            className={'w-full md:w-auto'}
                                        >
                                            Use
                                        </Button.Text>
                                        <Button.Danger
                                            type={'button'}
                                            disabled={isLoading}
                                            onClick={() => setDeleteCredentialProfile(viewCredentialProfile)}
                                            className={'w-full md:w-auto'}
                                        >
                                            Delete
                                        </Button.Danger>
                                        <Button type={'submit'} disabled={isLoading} className={'w-full md:w-auto'}>
                                            Save
                                        </Button>
                                    </div>
                                </div>
                            </form>
                        </>
                    ) : viewServerProfile ? (
                        <>
                            <form
                                id={'edit-server-profile'}
                                className={'flex flex-col h-full justify-between'}
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    setIsLoading(true);

                                    updateServerProfile(uuid, viewServerProfile.attributes.id, _name, _mode, host, port)
                                        .then(() => {
                                            mutate(
                                                (old) => ({
                                                    ...old,
                                                    servers: old.servers.map((profile) =>
                                                        profile.attributes.id === viewServerProfile.attributes.id
                                                            ? {
                                                                  ...profile,
                                                                  attributes: {
                                                                      ...profile.attributes,
                                                                      name: _name,
                                                                      host,
                                                                      port,
                                                                      mode: _mode,
                                                                  },
                                                              }
                                                            : profile
                                                    ),
                                                }),
                                                false
                                            );

                                            clearFlashes('importer:main');
                                        })
                                        .catch((error) => clearAndAddHttpError({ key: 'importer:main', error }))
                                        .finally(() => setIsLoading(false));
                                }}
                            >
                                <div className={'flex flex-col'}>
                                    <Label>Edit Server Profile</Label>

                                    <div className={'flex flex-row w-full items-center space-x-2 mt-4'}>
                                        <div className={'w-full'}>
                                            <Label>Name</Label>
                                            <Input
                                                value={_name}
                                                placeholder={'Name'}
                                                onChange={(e) => setName(e.target.value)}
                                                disabled={isLoading}
                                            />
                                        </div>
                                        <div className={'w-full'}>
                                            <Label>Host</Label>
                                            <Input
                                                value={host}
                                                placeholder={'Host'}
                                                onChange={(e) => setHost(e.target.value)}
                                                disabled={isLoading}
                                            />
                                        </div>
                                    </div>

                                    <div className={'flex flex-row w-full items-center space-x-2 mt-2'}>
                                        <div className={'w-full'}>
                                            <Label>Port</Label>
                                            <Input
                                                type={'number'}
                                                value={port}
                                                placeholder={'Port'}
                                                min={1}
                                                max={65535}
                                                onChange={(e) => setPort(e.target.valueAsNumber)}
                                                disabled={isLoading}
                                            />
                                        </div>
                                        <div className={'w-full'}>
                                            <Label>Mode</Label>
                                            <Select
                                                value={_mode}
                                                onChange={(e) => setMode(e.target.value as 'sftp' | 'ftp')}
                                                disabled={isLoading}
                                            >
                                                <option value={'sftp'}>SFTP</option>
                                                <option value={'ftp'}>FTP</option>
                                            </Select>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    className={
                                        'flex flex-col w-full md:flex-row md:space-y-0 space-y-2 items-center md:justify-between mt-4'
                                    }
                                >
                                    <Button.Text
                                        type={'button'}
                                        disabled={isLoading}
                                        onClick={() => setViewServerProfile(undefined)}
                                        className={'w-full md:w-auto'}
                                    >
                                        Cancel
                                    </Button.Text>

                                    <div className={'flex flex-row w-full items-center md:w-auto space-x-2'}>
                                        <Button.Text
                                            type={'button'}
                                            onClick={() => {
                                                setViewServerProfile(undefined);

                                                setImportHost(host);
                                                setImportPort(port);
                                                setImportMode(_mode);
                                            }}
                                            className={'w-full md:w-auto'}
                                        >
                                            Use
                                        </Button.Text>
                                        <Button.Danger
                                            type={'button'}
                                            disabled={isLoading}
                                            onClick={() => setDeleteServerProfile(viewServerProfile)}
                                            className={'w-full md:w-auto'}
                                        >
                                            Delete
                                        </Button.Danger>
                                        <Button type={'submit'} disabled={isLoading} className={'w-full md:w-auto'}>
                                            Save
                                        </Button>
                                    </div>
                                </div>
                            </form>
                        </>
                    ) : (
                        <>
                            <form id={'import-settings'} className={'flex flex-col h-full justify-between'}>
                                <div className={'flex flex-col'}>
                                    <Label>Import Settings</Label>

                                    <div className={'flex flex-row w-full items-center space-x-2 mt-4'}>
                                        <div className={'w-full'}>
                                            <Label>Host</Label>
                                            <Input
                                                value={importHost}
                                                placeholder={'Host'}
                                                onChange={(e) => setImportHost(e.target.value)}
                                            />
                                        </div>
                                        <div className={'w-full'}>
                                            <Label>Port</Label>
                                            <Input
                                                type={'number'}
                                                value={importPort}
                                                placeholder={'Port'}
                                                min={1}
                                                max={65535}
                                                onChange={(e) => setImportPort(e.target.valueAsNumber)}
                                            />
                                        </div>
                                    </div>

                                    <div className={'flex flex-row w-full items-center space-x-2 mt-2'}>
                                        <div className={'w-full'}>
                                            <Label>Username</Label>
                                            <Input
                                                value={importUsername}
                                                placeholder={'Username'}
                                                onChange={(e) => setImportUsername(e.target.value)}
                                            />
                                        </div>
                                        <div className={'w-full'}>
                                            <Label>Password</Label>
                                            <Input
                                                type={'password'}
                                                value={importPassword}
                                                placeholder={'Password'}
                                                onChange={(e) => setImportPassword(e.target.value)}
                                            />
                                        </div>
                                    </div>

                                    <div className={'flex flex-row w-full items-center space-x-2 mt-2'}>
                                        <div className={'w-full'}>
                                            <Label>Source</Label>
                                            <Input
                                                value={importFrom}
                                                placeholder={'/'}
                                                onChange={(e) => setImportFrom(e.target.value)}
                                            />
                                        </div>
                                        <div className={'w-full'}>
                                            <Label>Destination</Label>
                                            <Input
                                                value={importTo}
                                                placeholder={'/'}
                                                onChange={(e) => setImportTo(e.target.value)}
                                            />
                                        </div>
                                    </div>

                                    <div className={'flex flex-row w-full items-end space-x-2 mt-2'}>
                                        <div className={'w-full'}>
                                            <Label>Mode</Label>
                                            <Select
                                                value={importMode}
                                                onChange={(e) => setImportMode(e.target.value as 'sftp' | 'ftp')}
                                            >
                                                <option value={'sftp'}>SFTP</option>
                                                <option value={'ftp'}>FTP</option>
                                            </Select>
                                        </div>

                                        <div className={'flex flex-row w-full justify-end items-end space-x-2'}>
                                            <Button.Text
                                                type={'button'}
                                                onClick={() => {
                                                    testCredentials(
                                                        uuid,
                                                        importMode,
                                                        importUsername,
                                                        importPassword,
                                                        importHost,
                                                        importPort,
                                                        importFrom
                                                    )
                                                        .then(() => {
                                                            clearFlashes('importer:main');
                                                            setValidatedCredentials(
                                                                JSON.stringify({
                                                                    importHost,
                                                                    importPort,
                                                                    importUsername,
                                                                    importPassword,
                                                                    importMode,
                                                                    importFrom,
                                                                })
                                                            );
                                                        })
                                                        .catch((error) =>
                                                            clearAndAddHttpError({ key: 'importer:main', error })
                                                        );
                                                }}
                                                disabled={
                                                    !importHost ||
                                                    !importPort ||
                                                    !importUsername ||
                                                    !importPassword ||
                                                    isValidatedCredentials
                                                }
                                            >
                                                Test
                                            </Button.Text>
                                            <Button
                                                type={'button'}
                                                onClick={() => setConfirmImport(true)}
                                                disabled={
                                                    !importHost ||
                                                    !importPort ||
                                                    !importUsername ||
                                                    !importPassword ||
                                                    !isValidatedCredentials
                                                }
                                            >
                                                Import
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </>
                    )}
                </div>
            </div>
        </ServerContentBlock>
    );
}
