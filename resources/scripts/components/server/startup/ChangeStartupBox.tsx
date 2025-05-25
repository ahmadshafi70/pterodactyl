import React, { useEffect, useState } from 'react';
import { Form, Formik, FormikHelpers } from 'formik';
import Field from '@/components/elements/Field';
import { object, string } from 'yup';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import { ServerContext } from '@/state/server';
import useSWR from 'swr';
import getStartup from '@/api/server/startup/getStartup';
import useFlash from '@/plugins/useFlash';
import Spinner from '@/components/elements/Spinner';
import FlashMessageRender from '@/components/FlashMessageRender';
import ConfirmationModal from '@/components/elements/ConfirmationModal';
import changeStartup from '@/api/server/startup/changeStartup';
import resetStartup from '@/api/server/startup/resetStartup';
import Modal from '@/components/elements/Modal';

export interface StartupResponse {
    startup: string;
}

interface Values {
    startup: string;
}

interface Props {
    onChange: () => void;
}

export default ({ onChange }: Props) => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);

    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { data, error } = useSWR<StartupResponse>([uuid, '/startup/startup', React.useRef(Date.now())], (uuid) =>
        getStartup(uuid)
    );

    const [visible, setVisible] = useState(false);
    const [showConfirmation, setShowConfirmation] = useState(false);
    const [showReset, setShowReset] = useState(false);

    const save = ({ startup }: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('server:startup:startup');

        changeStartup(uuid, startup)
            .then(() => onChange())
            .catch((error) => clearAndAddHttpError({ key: 'server:startup:startup', error }))
            .finally(() => {
                setSubmitting(false);
                setShowConfirmation(false);
            });
    };

    const reset = () => {
        clearFlashes('server:startup:startup');

        resetStartup(uuid)
            .then(() => onChange())
            .catch((error) => clearAndAddHttpError({ key: 'server:startup:startup', error }))
            .finally(() => setShowReset(false));
    };

    useEffect(() => {
        if (!error) {
            clearFlashes('server:startup:startup');
        } else {
            clearAndAddHttpError({ key: 'server:startup:startup', error });
        }
    }, [error]);

    return (
        <div css={tw`mt-4`}>
            <FlashMessageRender byKey={'server:startup:startup'} css={tw`pb-4`} />
            {data ? (
                <>
                    <Formik
                        initialValues={{ startup: data.startup }}
                        onSubmit={save}
                        validationSchema={object().shape({
                            startup: string().required(),
                        })}
                    >
                        {({ isSubmitting, submitForm }) => (
                            <Form>
                                <Modal visible={visible} onDismissed={() => setVisible(false)}>
                                    <div css={tw`flex flex-wrap`}>
                                        <div css={tw`w-full mb-6`}>
                                            <Field label={'Startup Command'} name={'startup'} />
                                        </div>
                                    </div>
                                    <div css={tw`flex justify-end gap-2`}>
                                        <Button
                                            type={'button'}
                                            color={'grey'}
                                            onClick={() => {
                                                setVisible(false);
                                                setShowReset(true);
                                            }}
                                        >
                                            Restore to Default
                                        </Button>
                                        <Button
                                            type={'button'}
                                            color={'green'}
                                            disabled={isSubmitting}
                                            isLoading={isSubmitting}
                                            onClick={() => {
                                                setVisible(false);
                                                setShowConfirmation(true);
                                            }}
                                        >
                                            Save
                                        </Button>
                                    </div>
                                </Modal>
                                <ConfirmationModal
                                    visible={showConfirmation}
                                    onModalDismissed={() => setShowConfirmation(false)}
                                    onConfirmed={submitForm}
                                    title={'Confirm Startup Change'}
                                    buttonText={'Change'}
                                    showSpinnerOverlay={isSubmitting}
                                >
                                    Are you sure that you want to change the startup variable? This action can broke the
                                    server.
                                </ConfirmationModal>
                                <ConfirmationModal
                                    visible={showReset}
                                    onModalDismissed={() => setShowReset(false)}
                                    onConfirmed={reset}
                                    title={'Reset Startup Command'}
                                    buttonText={'Reset'}
                                >
                                    Are you sure that you want to reset the startup command?
                                </ConfirmationModal>
                            </Form>
                        )}
                    </Formik>
                    <div css={tw`flex justify-end`}>
                        <Button type={'button'} color={'primary'} size={'xsmall'} onClick={() => setVisible(true)}>
                            Change Startup Command
                        </Button>
                    </div>
                </>
            ) : (
                <>{!error && <Spinner size={'large'} centered />}</>
            )}
        </div>
    );
};
