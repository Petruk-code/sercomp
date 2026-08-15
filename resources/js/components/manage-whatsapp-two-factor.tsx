import { Form } from '@inertiajs/react';
import { MessageCircle } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { disable, enable } from '@/routes/security/whatsapp-2fa';

export type Props = {
    whatsapp2faEnabled?: boolean;
    whatsapp2faPhone?: string;
    whatsapp2faPhoneMasked?: string;
};

export default function ManageWhatsappTwoFactor(props: Props) {
    const enabled = props.whatsapp2faEnabled ?? false;
    const phoneMasked = props.whatsapp2faPhoneMasked ?? '';

    return (
        <div className="space-y-6">
            <Heading
                variant="small"
                title="WhatsApp two-factor authentication"
                description="Require a verification code sent to your WhatsApp when logging in"
            />
            {enabled ? (
                <div className="flex flex-col items-start justify-start space-y-4">
                    <p className="text-sm text-muted-foreground">
                        A login verification code will be sent to your WhatsApp
                        number ending in{' '}
                        <span className="font-medium text-foreground">
                            {phoneMasked}
                        </span>
                        . You will need this code each time you sign in.
                    </p>

                    <Form {...disable.form()}>
                        {({ processing }) => (
                            <Button
                                variant="destructive"
                                type="submit"
                                disabled={processing}
                            >
                                Disable WhatsApp 2FA
                            </Button>
                        )}
                    </Form>
                </div>
            ) : (
                <div className="flex flex-col items-start justify-start space-y-4">
                    <p className="text-sm text-muted-foreground">
                        {props.whatsapp2faPhone ? (
                            <>
                                When enabled, a verification code will be sent
                                to your WhatsApp number ending in{' '}
                                <span className="font-medium text-foreground">
                                    {phoneMasked}
                                </span>{' '}
                                each time you sign in.
                            </>
                        ) : (
                            <>
                                You need a WhatsApp number on your account
                                before enabling this. Ask your administrator to
                                set your phone number in your profile.
                            </>
                        )}
                    </p>

                    {props.whatsapp2faPhone ? (
                        <Form {...enable.form()}>
                            {({ processing }) => (
                                <Button type="submit" disabled={processing}>
                                    <MessageCircle />
                                    Enable WhatsApp 2FA
                                </Button>
                            )}
                        </Form>
                    ) : null}
                </div>
            )}
        </div>
    );
}
