import React, { useEffect, useState } from 'react';
import Modal, { RequiredModalProps } from '@/components/elements/Modal';
import { Form, Formik, FormikHelpers, useFormikContext, Field as FormikField } from 'formik';
import { object, string, number } from 'yup';
import Field from '@/components/elements/Field';
import useFlash from '@/plugins/useFlash';
import FlashMessageRender from '@/components/FlashMessageRender';
import Button from '@/components/elements/Button';
import tw from 'twin.macro';
import { ServerContext } from '@/state/server';
import createDatabaseBackup from '@/api/server/databases/backup/createDatabaseBackup';
import Label from '@/components/elements/Label';
import FormikFieldWrapper from '@/components/elements/FormikFieldWrapper';
import Select from '@/components/elements/Select';

interface Values {
    name: string;
    database: number;
}

const ModalContent = ({ databases, ...props }: RequiredModalProps & { databases: any[] }) => {
    const { isSubmitting } = useFormikContext<Values>();

    return (
        <Modal {...props} showSpinnerOverlay={isSubmitting}>
            <Form>
                <FlashMessageRender byKey={'database:backups:create'} css={tw`mb-4`} />
                <h2 css={tw`text-2xl mb-6`}>Create database backup</h2>
                <Field
                    name={'name'}
                    label={'Backup name'}
                    description={'If provided, the name that should be used to reference this backup.'}
                />
                <div css={tw`w-full pt-4`}>
                    <Label>Database</Label>
                    <FormikFieldWrapper name={'database'}>
                        <FormikField as={Select} name={'database'}>
                            {databases.map((item, key) => (
                                <option key={key} value={item.id}>
                                    {item.database}
                                </option>
                            ))}
                        </FormikField>
                    </FormikFieldWrapper>
                </div>
                <div css={tw`flex justify-end mt-6`}>
                    <Button type={'submit'} disabled={isSubmitting}>
                        Start backup
                    </Button>
                </div>
            </Form>
        </Modal>
    );
};

interface Props {
    databases: any[];
    onCreated: () => void;
}

export default ({ databases, onCreated }: Props) => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        clearFlashes('database:backup:create');
    }, [visible]);

    const submit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('database:backups:create');
        createDatabaseBackup(uuid, values.name, values.database)
            .then(() => {
                setVisible(false);
                onCreated();
            })
            .catch((error) => {
                clearAndAddHttpError({ key: 'database:backups:create', error });
                setSubmitting(false);
            });
    };

    return (
        <>
            {visible && (
                <Formik
                    onSubmit={submit}
                    initialValues={{ name: '', database: databases[0].id }}
                    validationSchema={object().shape({
                        name: string().required().max(191),
                        database: number().required(),
                    })}
                >
                    <ModalContent
                        appear
                        visible={visible}
                        onDismissed={() => setVisible(false)}
                        databases={databases}
                    />
                </Formik>
            )}
            <Button css={tw`w-full sm:w-auto`} onClick={() => setVisible(true)}>
                Create backup
            </Button>
        </>
    );
};
