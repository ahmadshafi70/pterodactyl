import React, { useEffect, useState } from 'react';
import { ServerContext } from '@/state/server';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import Spinner from '@/components/elements/Spinner';
import useSWR from 'swr';
import useFlash from '@/plugins/useFlash';
import getData, { Subdomain } from './api/getData';
import Can from '@/components/elements/Can';
import FlashMessageRender from '@/components/FlashMessageRender';
import { Button } from '@/components/elements/button/index';
import GreyRowBox from '@/components/elements/GreyRowBox';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faGlobe, faTrashAlt } from '@fortawesome/free-solid-svg-icons';
import { format, formatDistanceToNow } from 'date-fns';
import CopyOnClick from '@/components/elements/CopyOnClick';
import Code from '@/components/elements/Code';
import { Dialog } from '@/components/elements/dialog/index';
import deleteSubdomain from './api/deleteSubdomain';
import updateSubdomain from './api/updateSubdomain';
import createSubdomain from './api/createSubdomain';
import Label from '@/components/elements/Label';
import Select from '@/components/elements/Select';
import Input from '@/components/elements/Input';
import { Allocation } from '@/api/server/getServer';

export default function SubdomainManagerContainer() {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const allocations = ServerContext.useStoreState((state) => state.server.data!.allocations);
    const [subdomain, setSubdomain] = useState<Subdomain>();
    const [create, setCreate] = useState(false);
    const [_deleteSubdomain, setDeleteSubdomain] = useState<Subdomain>();
    const [allocation, setAllocation] = useState<Allocation>();
    const [loading, setLoading] = useState(false);
    const [rawSubdomain, setRawSubdomain] = useState('');
    const [domain, setDomain] = useState<number>();

    const { clearFlashes, clearAndAddHttpError } = useFlash();

    const { data, mutate } = useSWR(['subdomains', 'data', uuid], () => getData(uuid), {
        refreshInterval: 10000,
    });

    useEffect(() => {
        clearFlashes('subdomains:main');
        clearFlashes('subdomains:create');
    }, []);

    useEffect(() => {
        if (data && data.domains.length > 0) {
            setDomain(data.domains[0].id);
        }
    }, [data]);

    if (!data) {
        return <Spinner size={'large'} centered />;
    }

    return (
        <ServerContentBlock title={'Subdomains'}>
            <FlashMessageRender byKey={'subdomains:main'} className={'mb-4'} />

            <Dialog.Confirm
                title={`Delete "${_deleteSubdomain?.attributes.subdomain}.${_deleteSubdomain?.attributes.domain}"`}
                open={!!_deleteSubdomain}
                onClose={() => setDeleteSubdomain(undefined)}
                confirm={'Delete'}
                onConfirmed={() => {
                    deleteSubdomain(uuid, _deleteSubdomain?.attributes.id ?? 0)
                        .then(() => {
                            mutate(
                                (old) => ({
                                    ...old,
                                    subdomains: old.subdomains.filter(
                                        (s) => s.attributes.id !== _deleteSubdomain?.attributes.id
                                    ),
                                }),
                                false
                            );

                            setDeleteSubdomain(undefined);
                            clearFlashes('subdomains:main');
                        })
                        .catch((error) => {
                            clearAndAddHttpError({ error, key: 'subdomains:main' });
                            setDeleteSubdomain(undefined);
                        });
                }}
            >
                Are you sure you want to delete this subdomain? This action cannot be undone.
            </Dialog.Confirm>

            <Dialog open={create} onClose={() => setCreate(false)} title={'Create new Subdomain'}>
                <form
                    id={'create-subdomain-form'}
                    onSubmit={(e) => {
                        e.preventDefault();
                        if (loading) return;
                        setLoading(true);

                        createSubdomain(uuid, rawSubdomain, domain ?? 0, allocation?.id ?? allocations.find((a) => a.isDefault)?.id ?? 0)
                            .then((subdomain) => {
                                setRawSubdomain('');
                                setDomain(data.domains[0].id);
                                setAllocation(undefined);

                                clearFlashes('subdomains:create');
                                mutate((old) => ({ ...old, subdomains: [...old.subdomains, subdomain] }), false);
                                setCreate(false);
                            })
                            .catch((error) => {
                                console.error(error);
                                clearAndAddHttpError({ error, key: 'subdomains:create' });
                            })
                            .finally(() => setLoading(false));
                    }}
                >
                    <FlashMessageRender byKey={'subdomains:create'} className={'mb-6'} />

                    <div className={'grid w-full grid-cols-5 gap-2'}>
                        <div className={'flex flex-col col-span-3'}>
                            <Label>Subdomain</Label>
                            <Input
                                placeholder={'myserver'}
                                value={rawSubdomain}
                                onChange={(e) => setRawSubdomain(e.target.value)}
                            />
                        </div>
                        <div className={'flex flex-col col-span-2'}>
                            <Label>Domain</Label>
                            <Select value={domain} onChange={(e) => setDomain(parseInt(e.target.value))}>
                                {data.domains.map((domain) => (
                                    <option key={domain.id} value={domain.id.toString()}>
                                        {domain.domain}
                                    </option>
                                ))}
                            </Select>
                        </div>
                    </div>

                    <div className={'mt-4'}>
                        <Label>Allocation</Label>
                        <Select
                            value={allocation?.id ?? allocations.find((a) => a.isDefault)?.id}
                            onChange={(e) => setAllocation(allocations.find((a) => a.id === parseInt(e.target.value)))}
                        >
                            <optgroup label={'Default'}>
                                {allocations.find((a) => a.isDefault) && (
                                    <option value={allocations.find((a) => a.isDefault)?.id.toString()}>
                                        {allocations.find((a) => a.isDefault)?.alias ?? allocations.find((a) => a.isDefault)?.ip}:
                                        {allocations.find((a) => a.isDefault)?.port}
                                    </option>
                                )}
                            </optgroup>
                            {allocations.filter((a) => !a.isDefault).length > 0 && (
                                <optgroup label={`Additional (${allocations.filter((a) => !a.isDefault).length})`}>
                                    {allocations.filter((a) => !a.isDefault).map((allocation) => (
                                        <option key={allocation.id} value={allocation.id.toString()}>
                                            {allocation.alias ?? allocation.ip}:{allocation.port}
                                        </option>
                                    ))}
                                </optgroup>
                            )}
                        </Select>
                    </div>

                    <Dialog.Footer>
                        <Button.Text onClick={() => setCreate(false)}>Cancel</Button.Text>
                        <Button
                            type={'submit'}
                            form={'create-subdomain-form'}
                            disabled={loading || rawSubdomain.length < 3 || rawSubdomain.length > 32}
                        >
                            Create Subdomain
                        </Button>
                    </Dialog.Footer>
                </form>
            </Dialog>

            <Dialog
                title={`Update "${subdomain?.attributes.subdomain}.${subdomain?.attributes.domain}"`}
                open={!!subdomain}
                onClose={() => setSubdomain(undefined)}
            >
                <form
                    id={'update-subdomain-form'}
                    onSubmit={(e) => {
                        e.preventDefault();
                        if (loading) return;
                        setLoading(true);

                        updateSubdomain(uuid, subdomain?.attributes.id ?? 0, allocation?.id ?? 0)
                            .then(() => {
                                clearFlashes('subdomains:main');
                                mutate();
                                setSubdomain(undefined);
                            })
                            .catch((error) => {
                                console.error(error);
                                clearAndAddHttpError({ error, key: 'subdomains:main' });
                            })
                            .finally(() => setLoading(false));
                    }}
                >
                    <FlashMessageRender byKey={'subdomains:update'} className={'mb-6'} />

                    <div className={'grid grid-cols-5 gap-2'}>
                        <div className={'flex flex-col col-span-3'}>
                            <Label>Subdomain</Label>
                            <Input
                                value={subdomain?.attributes.subdomain}
                                disabled
                            />
                        </div>
                        <div className={'flex flex-col col-span-2'}>
                            <Label>Domain</Label>
                            <Select
                                value={subdomain?.attributes.domain}
                                disabled
                            >
                                {data.domains.map((domain) => (
                                    <option key={domain.id} value={domain.id.toString()}>
                                        {domain.domain}
                                    </option>
                                ))}
                            </Select>
                        </div>
                    </div>

                    <div className={'mt-4'}>
                        <Label>Allocation</Label>
                        <Select
                            value={allocation?.id ?? subdomain?.attributes.allocation?.id ?? 0}
                            onChange={(e) => setAllocation(allocations.find((a) => a.id === parseInt(e.target.value)))}
                        >
                            <option value={0} disabled hidden>
                                Unknown
                            </option>
                            <optgroup label={'Default'}>
                                {allocations.find((a) => a.isDefault) && (
                                    <option value={allocations.find((a) => a.isDefault)?.id.toString()}>
                                        {allocations.find((a) => a.isDefault)?.alias ?? allocations.find((a) => a.isDefault)?.ip}:
                                        {allocations.find((a) => a.isDefault)?.port}
                                    </option>
                                )}
                            </optgroup>
                            {allocations.filter((a) => !a.isDefault).length > 0 && (
                                <optgroup label={`Additional (${allocations.filter((a) => !a.isDefault).length})`}>
                                    {allocations.filter((a) => !a.isDefault).map((allocation) => (
                                        <option key={allocation.id} value={allocation.id.toString()}>
                                            {allocation.alias ?? allocation.ip}:{allocation.port}
                                        </option>
                                    ))}
                                </optgroup>
                            )}
                        </Select>
                    </div>

                    <Dialog.Footer>
                        <Button.Text onClick={() => setSubdomain(undefined)}>Cancel</Button.Text>
                        <Button
                            type={'submit'}
                            form={'update-subdomain-form'}
                            disabled={loading || !allocation}
                        >
                            Update Subdomain
                        </Button>
                    </Dialog.Footer>
                </form>
            </Dialog>

            {data.subdomains.map((subdomain, i) => (
                <GreyRowBox $hoverable={false} className={'flex-wrap md:flex-nowrap mt-2 justify-between'} key={i}>
                    <div className={'flex items-center w-full md:w-auto'}>
                        <div className={'pl-4 pr-6 text-neutral-400'}>
                            <FontAwesomeIcon icon={faGlobe} />
                        </div>
                        <div className={'flex flex-col truncate'}>
                            <div className={'flex items-center text-sm'}>
                                <CopyOnClick text={`${subdomain.attributes.subdomain}.${subdomain.attributes.domain}`}>
                                    <div>
                                        <Code dark className={'break-words truncate'}>
                                            {subdomain.attributes.subdomain}.{subdomain.attributes.domain}
                                        </Code>
                                    </div>
                                </CopyOnClick>
                            </div>
                            <p className={'text-2xs text-neutral-500 uppercase mt-1'}>Domain</p>
                        </div>
                    </div>
                    <div className={'flex flex-row items-center justify-between md:justify-end w-full md:w-1/2'}>
                        <div className={'flex flex-col mr-8 md:w-52 md:text-center'}>
                            <p
                                title={format(new Date(subdomain.attributes.created_at), 'ddd, MMMM do, yyyy HH:mm:ss')}
                                className={'text-sm'}
                            >
                                {formatDistanceToNow(new Date(subdomain.attributes.created_at), {
                                    includeSeconds: true,
                                    addSuffix: true,
                                })}
                            </p>
                            <p className={'text-2xs text-neutral-500 uppercase mt-1'}>Created</p>
                        </div>
                        <div className={'flex flex-row justify-self-end items-end'}>
                            <Can action={'subdomains.update'}>
                                <Button.Text
                                    size={Button.Sizes.Small}
                                    type={'button'}
                                    className={'mr-2'}
                                    onClick={() => setSubdomain(subdomain)}
                                >
                                    Update
                                </Button.Text>
                            </Can>
                            <Can action={'subdomains.delete'}>
                                <Button.Danger
                                    variant={Button.Variants.Secondary}
                                    size={Button.Sizes.Small}
                                    shape={Button.Shapes.IconSquare}
                                    type={'button'}
                                    onClick={() => setDeleteSubdomain(subdomain)}
                                >
                                    <FontAwesomeIcon icon={faTrashAlt} className={'w-3 h-auto'} />
                                </Button.Danger>
                            </Can>
                        </div>
                    </div>
                </GreyRowBox>
            ))}

            {data.subdomains.length === 0 && (
                <p className={'text-center text-sm text-neutral-300'}>
                    There are no subdomains currently configured for this server.
                </p>
            )}
            {data.limit === 0 && (
                <p className={'text-center text-sm text-neutral-300'}>
                    Subdomains cannot be created for this server because the subdomain limit is set to 0.
                </p>
            )}

            <Can action={'subdomains.create'}>
                <div className={'mt-6 sm:flex items-center justify-end'}>
                    {data.limit > 0 && data.subdomains.length > 0 && (
                        <p className={'text-sm text-neutral-300 mb-4 sm:mr-6 sm:mb-0'}>
                            {data.subdomains.length} of {data.limit} subdomains have been created for this server.
                        </p>
                    )}
                    {data.limit > 0 && data.limit > data.subdomains.length && (
                        <Button disabled={!data.domains.length} onClick={() => setCreate(true)}>
                            Create Subdomain
                        </Button>
                    )}
                </div>
            </Can>
        </ServerContentBlock>
    );
}
