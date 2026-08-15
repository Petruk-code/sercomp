import { Form, Head, setLayoutProps } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { OTP_MAX_LENGTH } from '@/hooks/use-two-factor-auth';
import { resend, verify } from '@/routes/two-factor/whatsapp';

type Props = {
    phoneMasked: string;
};

setLayoutProps({
    title: 'WhatsApp verification code',
    description:
        'Enter the verification code that was sent to your WhatsApp number.',
});

export default function WhatsappOtpChallenge(props: Props) {
    const [code, setCode] = useState<string>('');

    return (
        <>
            <Head title="WhatsApp verification code" />

            <div className="space-y-6">
                <Form
                    {...verify.form()}
                    className="space-y-4"
                    resetOnError
                    resetOnSuccess
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="flex flex-col items-center justify-center space-y-3 text-center">
                                <p className="text-sm text-muted-foreground">
                                    We sent a 6-digit code to your WhatsApp
                                    number ending in{' '}
                                    <span className="font-medium text-foreground">
                                        {props.phoneMasked}
                                    </span>
                                    .
                                </p>

                                <div className="flex w-full items-center justify-center">
                                    <InputOTP
                                        name="code"
                                        maxLength={OTP_MAX_LENGTH}
                                        value={code}
                                        onChange={(value) => setCode(value)}
                                        disabled={processing}
                                        pattern={REGEXP_ONLY_DIGITS}
                                        autoFocus
                                    >
                                        <InputOTPGroup>
                                            {Array.from(
                                                { length: OTP_MAX_LENGTH },
                                                (_, index) => (
                                                    <InputOTPSlot
                                                        key={index}
                                                        index={index}
                                                    />
                                                ),
                                            )}
                                        </InputOTPGroup>
                                    </InputOTP>
                                </div>
                                <InputError message={errors.code} />
                            </div>

                            <Button
                                type="submit"
                                className="w-full"
                                disabled={processing}
                            >
                                Continue
                            </Button>
                        </>
                    )}
                </Form>

                <div className="flex items-center justify-between text-sm text-muted-foreground">
                    <span>Didn&apos;t receive the code?</span>
                    <Form {...resend.form()}>
                        {({ processing }) => (
                            <button
                                type="submit"
                                disabled={processing}
                                className="cursor-pointer font-medium text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            >
                                Resend code
                            </button>
                        )}
                    </Form>
                </div>
            </div>
        </>
    );
}
